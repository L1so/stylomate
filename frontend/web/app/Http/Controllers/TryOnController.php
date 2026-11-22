<?php

namespace App\Http\Controllers;

use App\Services\Fashion\Fashion;
use App\Services\Fashion\TryOnResults;
use Illuminate\Http\Request;

/**
 * Virtual Try-On. Halaman diisi Alpine.js; generate & save dipanggil lewat fetch (JSON).
 * Browser TIDAK memanggil backend/AI; hanya memanggil controller ini.
 */
class TryOnController extends Controller
{
    /** Bentuk "garment" (pakaian untuk try-on) dari item wardrobe/katalog. */
    private function garment(string $source, $item): array
    {
        return [
            'source'   => $source,
            'id'       => $item['id'],
            'name'     => $item['name'],
            'category' => $item['category'],
            'imageUrl' => $item['imageUrl'],
        ];
    }

    private function resolveGarment(string $source, $id): array
    {
        $item = $source === 'wardrobe' ? Fashion::wardrobe()->getItem($id) : Fashion::catalog()->getItem($id);
        return $this->garment($source, $item);
    }

    // GET /try-on?wardrobe=ID  atau  /try-on?catalog=ID
    public function index(Request $request)
    {
        $wardrobe = array_map(fn ($i) => $this->garment('wardrobe', $i), Fashion::wardrobe()->listItems());
        $catalog = array_map(fn ($i) => $this->garment('catalog', $i), Fashion::catalog()->listItems());

        $preload = null;
        if ($request->query('wardrobe') !== null) {
            $preload = $this->resolveGarment('wardrobe', $request->query('wardrobe'));
        } elseif ($request->query('catalog') !== null) {
            $preload = $this->resolveGarment('catalog', $request->query('catalog'));
        }

        return view('try-on.index', compact('wardrobe', 'catalog', 'preload'));
    }

    // POST /try-on/generate (multipart) -> JSON {id, imageUrl}
    public function generate(Request $request)
    {
        set_time_limit(200); // inferensi AI bisa lama; bawaan PHP built-in server 30 detik

        $data = $request->validate([
            'person_image' => ['required', 'file', 'mimetypes:image/jpeg,image/png', 'max:10240'],
            'source'       => ['required', 'in:wardrobe,catalog'],
            'garment_id'   => ['required'],
        ], [
            'person_image.required'  => 'Choose your photo first (if you did, it may be larger than the server upload limit; see README).',
            'person_image.mimetypes' => 'Use a JPEG or PNG image.',
            'person_image.max'       => 'The image is larger than 10 MB. Choose a smaller one.',
            'person_image.uploaded'  => 'The photo could not be uploaded. It may be larger than the server limit (see README).',
            'source.in'              => 'Choose an item to try on.',
            'garment_id.required'    => 'Choose an item to try on.',
        ]);

        $garment = $this->resolveGarment($data['source'], $data['garment_id']);
        return response()->json(Fashion::tryOn()->generate($request->file('person_image'), $garment));
    }

    // POST /try-on/save (JSON: id, source, garment_id) -> JSON
    public function save(Request $request)
    {
        $data = $request->validate([
            'id'         => ['nullable'],
            'source'     => ['required', 'in:wardrobe,catalog'],
            'garment_id' => ['required'],
        ]);

        $garment = $this->resolveGarment($data['source'], $data['garment_id']);
        Fashion::tryOn()->saveResult($data['id'] ?? null, $garment);
        return response()->json(['saved' => true]);
    }

    // GET /try-on/result/{file}: menayangkan gambar hasil sementara
    public function result(string $file)
    {
        $path = TryOnResults::path($file);
        abort_if($path === null, 404);
        return response()->file($path, ['Cache-Control' => 'private, max-age=3600']);
    }
}
