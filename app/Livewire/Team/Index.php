<?php

namespace App\Livewire\Team;

use App\Livewire\Concerns\Sortable;
use App\Mail\EmployeeInvitationMail;
use App\Models\EmployeeInvitation;
use App\Models\User;
use App\Permission;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Livewire\Component;

class Index extends Component
{
    use Sortable;

    public string $search = '';

    public bool $showInviteModal = false;

    public string $inviteEmail = '';

    public string $inviteRole = 'kasir';

    /** @var array<int, string> */
    public array $invitePermissions = [];

    public ?string $lastInviteUrl = null;

    public bool $showEditModal = false;

    public ?int $editingUserId = null;

    public string $editName = '';

    public string $editRole = 'kasir';

    /** @var array<int, string> */
    public array $editPermissions = [];

    public function openInviteModal(): void
    {
        $this->reset(['inviteEmail', 'inviteRole', 'invitePermissions', 'lastInviteUrl']);
        $this->inviteRole = 'kasir';
        $this->showInviteModal = true;
    }

    public function sendInvite(): void
    {
        abort_unless(Auth::user()->isOwner(), 403);

        $store = Auth::user()->store;

        $validated = $this->validate([
            'inviteEmail' => ['required', 'email', 'max:255'],
            'inviteRole' => ['required', 'in:owner,kasir'],
            'invitePermissions' => ['array'],
            'invitePermissions.*' => ['in:'.implode(',', array_column(Permission::cases(), 'value'))],
        ]);

        if (User::where('email', $validated['inviteEmail'])->exists()) {
            $this->addError('inviteEmail', 'Email ini sudah terdaftar sebagai pengguna.');

            return;
        }

        if ($store->employeeInvitations()->whereNull('accepted_at')->where('email', $validated['inviteEmail'])->exists()) {
            $this->addError('inviteEmail', 'Undangan untuk email ini sudah dikirim sebelumnya.');

            return;
        }

        $invitation = EmployeeInvitation::create([
            'store_id' => $store->id,
            'invited_by' => Auth::id(),
            'email' => $validated['inviteEmail'],
            'role' => $validated['inviteRole'],
            'permissions' => $validated['inviteRole'] === 'kasir' ? $validated['invitePermissions'] : null,
            'expires_at' => now()->addDays(7),
        ]);

        $this->sendInvitationMail($invitation);

        $this->showInviteModal = false;
    }

    public function resendInvite(int $invitationId): void
    {
        abort_unless(Auth::user()->isOwner(), 403);

        $invitation = Auth::user()->store->employeeInvitations()->whereNull('accepted_at')->findOrFail($invitationId);
        $invitation->update(['expires_at' => now()->addDays(7)]);

        $this->sendInvitationMail($invitation);
    }

    public function cancelInvite(int $invitationId): void
    {
        abort_unless(Auth::user()->isOwner(), 403);

        Auth::user()->store->employeeInvitations()->whereNull('accepted_at')->findOrFail($invitationId)->delete();
    }

    private function sendInvitationMail(EmployeeInvitation $invitation): void
    {
        $url = URL::temporarySignedRoute('invitations.accept', $invitation->expires_at, ['invitation' => $invitation->id]);

        Mail::to($invitation->email)->send(new EmployeeInvitationMail($invitation, $url));

        $this->lastInviteUrl = $url;
    }

    public function openEditModal(int $userId): void
    {
        $user = Auth::user()->store->users()->findOrFail($userId);

        $this->editingUserId = $user->id;
        $this->editName = $user->name;
        $this->editRole = $user->role;
        $this->editPermissions = $user->permissions ?? [];
        $this->showEditModal = true;
    }

    public function saveEdit(): void
    {
        abort_unless(Auth::user()->isOwner(), 403);

        if ($this->editingUserId === Auth::id()) {
            $this->addError('editRole', 'Anda tidak bisa mengubah role atau izin akun Anda sendiri.');

            return;
        }

        $validated = $this->validate([
            'editName' => ['required', 'string', 'max:255'],
            'editRole' => ['required', 'in:owner,kasir'],
            'editPermissions' => ['array'],
            'editPermissions.*' => ['in:'.implode(',', array_column(Permission::cases(), 'value'))],
        ]);

        $user = Auth::user()->store->users()->findOrFail($this->editingUserId);

        if ($user->isOwner() && $validated['editRole'] !== 'owner' && $this->remainingOwnerCount() <= 1) {
            $this->addError('editRole', 'Toko harus memiliki minimal satu owner.');

            return;
        }

        $user->update([
            'name' => $validated['editName'],
            'role' => $validated['editRole'],
            'permissions' => $validated['editRole'] === 'kasir' ? $validated['editPermissions'] : null,
        ]);

        $this->showEditModal = false;
    }

    public function toggleActive(int $userId): void
    {
        abort_unless(Auth::user()->isOwner(), 403);

        if ($userId === Auth::id()) {
            return;
        }

        $user = Auth::user()->store->users()->findOrFail($userId);

        if ($user->isOwner() && $user->is_active && $this->remainingOwnerCount() <= 1) {
            $this->addError('editRole', 'Toko harus memiliki minimal satu owner yang aktif.');

            return;
        }

        $user->update(['is_active' => ! $user->is_active]);
    }

    private function remainingOwnerCount(): int
    {
        return Auth::user()->store->users()->where('role', 'owner')->where('is_active', true)->count();
    }

    public function render(): View
    {
        $store = Auth::user()->store;

        return view('livewire.team.index', [
            'employees' => $store->users()
                ->when($this->search, fn ($query) => $query->where(fn ($q) => $q
                    ->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
                ))
                ->orderBy($this->sortField ?: 'role', $this->sortField ? $this->sortDirection : 'desc')
                ->orderBy('name')
                ->get(),
            'pendingInvitations' => $store->employeeInvitations()->whereNull('accepted_at')->latest()->get(),
            'permissions' => Permission::cases(),
        ]);
    }
}
