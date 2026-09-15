<div style="font-family: sans-serif; max-width: 480px; margin: 0 auto; padding: 24px;">
    <h2 style="color: #1c2e74;">{{ $invitation->store->name }}</h2>
    <p>Anda diundang oleh <strong>{{ $invitation->inviter->name }}</strong> untuk bergabung sebagai
        <strong>{{ $invitation->role === 'owner' ? 'Owner' : 'Karyawan' }}</strong> di
        <strong>{{ $invitation->store->name }}</strong> pada NichmattPOS.</p>

    <p>
        <a href="{{ $acceptUrl }}" style="display: inline-block; background: #2a4ce0; color: #fff; padding: 10px 20px; border-radius: 8px; text-decoration: none;">
            Terima Undangan
        </a>
    </p>

    <p style="color: #64748b; font-size: 13px;">Link ini berlaku selama 7 hari. Jika Anda tidak merasa mendaftar, abaikan email ini.</p>
</div>
