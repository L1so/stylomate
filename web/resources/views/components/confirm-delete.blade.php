{{-- Hapus lewat form DELETE + @csrf, bukan link GET (PAWL P4/P8). Konfirmasi via data-confirm di app.js --}}
@props(['action', 'message' => 'Yakin ingin menghapus? Tindakan ini tidak bisa dibatalkan.', 'label' => 'Hapus'])
<form action="{{ $action }}" method="POST" data-confirm="{{ $message }}" class="inline">
    @csrf
    @method('DELETE')
    <x-button :label="$label" variant="danger" />
</form>
