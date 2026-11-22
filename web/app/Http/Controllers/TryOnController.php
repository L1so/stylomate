<?php

namespace App\Http\Controllers;

use App\Exceptions\FastApiException;
use App\Http\Requests\GenerateTryOnRequest;
use App\Services\FastApiClient;
use Illuminate\Http\Request;

class TryOnController extends Controller
{
    public function index(Request $request, FastApiClient $api) // GET /try-on?catalog_item_id=3
    {
        $result = session('try_on_result');
        $selected = old('item') ?? (is_array($result) ? ($result['item'] ?? null) : null);
        $notice = null;

        $catalogTops = $api->catalog(['category' => 'top']);   // MVP: hanya kategori top
        $wardrobeTops = array_values(array_filter($api->wardrobeItems(), fn (array $item) => ($item['category'] ?? null) === 'top'));

        // Tombol "Try On" di Catalog / Wardrobe membawa id item lewat query string
        $catalogId = $this->idFrom($request->query('catalog_item_id'));
        $wardrobeId = $this->idFrom($request->query('wardrobe_item_id'));

        if ($catalogId !== null) {
            $catalogItem = $api->catalogItem($catalogId);   // 404 ditangani handler global

            if (($catalogItem['category'] ?? null) === 'top') {
                if (! collect($catalogTops)->contains('id', $catalogItem['id'])) {
                    array_unshift($catalogTops, $catalogItem);
                }
                $selected ??= 'catalog:'.$catalogId;
            } else {
                $notice = 'Try On saat ini baru mendukung atasan. Pilih atasan dari Catalog atau Wardrobe.';
            }
        } elseif ($wardrobeId !== null) {
            if (collect($wardrobeTops)->contains('id', $wardrobeId)) {
                $selected ??= 'wardrobe:'.$wardrobeId;
            } else {
                $notice = 'Try On saat ini baru mendukung atasan. Pilih atasan dari Catalog atau Wardrobe.';
            }
        }

        return view('try-on.index', [
            'catalogTops' => $catalogTops,
            'wardrobeTops' => $wardrobeTops,
            'selected' => is_string($selected) ? $selected : null,
            'notice' => $notice,
            'result' => $result,
        ]);
    }

    public function generate(GenerateTryOnRequest $request, FastApiClient $api) // POST /try-on/generate (multipart)
    {
        [$wardrobeId, $catalogId] = $request->selectedIds();

        try {
            $result = $api->generateTryOn($request->file('person_image'), $wardrobeId, $catalogId);
        } catch (FastApiException $e) {
            if ($e->status === 401) {
                throw $e;   // sesi habis: ditangani handler global
            }

            // Timeout AI / server error: kembali ke form dengan pesan aman, pilihan item tetap terisi (foto harus diunggah ulang)
            return back()->withInput($request->only('item'))->with('error', $e->getMessage());
        }

        // PRG: hasil dibawa lewat flash session supaya refresh tidak mengirim ulang foto
        return redirect()->route('try-on.index')->with('try_on_result', [
            'id' => $result['id'],
            'image_url' => $result['image_url'],
            'item' => $request->validated('item'),
        ]);
    }

    public function save(Request $request, FastApiClient $api) // POST /try-on/save {generation_id}
    {
        $data = $request->validate(['generation_id' => ['required', 'integer', 'min:1']], [
            'generation_id.required' => 'Hasil Try On tidak ditemukan. Buat preview lagi.',
            'generation_id.integer' => 'Hasil Try On tidak valid.',
            'generation_id.min' => 'Hasil Try On tidak valid.',
        ]);

        $api->saveTryOn((int) $data['generation_id']);

        return redirect()->route('profile.index', ['tab' => 'try-on'])->with('success', 'Hasil Try On disimpan ke profil.');
    }

    public function destroy(FastApiClient $api, int $id) // DELETE /try-on/{id}
    {
        $api->deleteTryOn($id);

        return redirect()->route('profile.index')->with('success', 'Hasil Try On dihapus.');
    }

    private function idFrom(mixed $value): ?int
    {
        return is_string($value) && ctype_digit($value) && (int) $value > 0 ? (int) $value : null;
    }
}
