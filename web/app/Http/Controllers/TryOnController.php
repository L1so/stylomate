<?php

namespace App\Http\Controllers;

use App\Exceptions\FastApiException;
use App\Http\Requests\GenerateTryOnRequest;
use App\Services\FastApiClient;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class TryOnController extends Controller
{
    public function warmup(FastApiClient $api): Response
    {
        if (config('services.fastapi.ai_warmup') && ! config('services.fastapi.use_mock')
            && Cache::add('stylo-ai-warmup', true, 30)) {
            $api->warmTryOn();
        }

        return response()->noContent();
    }

    public function index(Request $request, FastApiClient $api) // GET /try-on?outfit_id=3
    {
        $result = session('try_on_result');
        $selected = old('item') ?? (is_array($result) ? ($result['item'] ?? null) : null);
        $notice = null;

        $supported = ['top', 'bottom'];
        $catalogItems = array_values(array_filter(
            $api->catalog(),
            fn (array $item) => in_array($item['category'] ?? null, $supported, true),
        ));
        $wardrobeItems = array_values(array_filter(
            $api->wardrobeItems(),
            fn (array $item) => in_array($item['category'] ?? null, $supported, true),
        ));

        // Outfit Try On hanya memakai tepat satu atasan dan satu bawahan. Item lain tetap ada di outfit,
        // tetapi tidak dikirim ke model karena checkpoint saat ini hanya mendukung dua kategori tersebut.
        $wardrobeById = collect($wardrobeItems)->keyBy('id');
        $outfits = [];
        foreach ($api->outfits() as $outfit) {
            $items = collect($outfit['wardrobe_item_ids'] ?? [])
                ->map(fn ($id) => is_numeric($id) ? $wardrobeById->get((int) $id) : null)
                ->filter(fn ($item) => is_array($item))
                ->values();
            $tops = $items->where('category', 'top')->values();
            $bottoms = $items->where('category', 'bottom')->values();

            if ($tops->count() === 1 && $bottoms->count() === 1) {
                $outfit['try_on_items'] = [$tops->first(), $bottoms->first()];
                $outfits[] = $outfit;
            }
        }

        // Tombol "Try On" di Catalog, Wardrobe, atau Profile membawa sumber lewat query string.
        $catalogId = $this->idFrom($request->query('catalog_item_id'));
        $wardrobeId = $this->idFrom($request->query('wardrobe_item_id'));
        $outfitId = $this->idFrom($request->query('outfit_id'));

        if ($catalogId !== null) {
            $catalogItem = $api->catalogItem($catalogId);   // 404 ditangani handler global

            if (in_array($catalogItem['category'] ?? null, $supported, true)) {
                if (! collect($catalogItems)->contains('id', $catalogItem['id'])) {
                    array_unshift($catalogItems, $catalogItem);
                }
                $selected ??= 'catalog:'.$catalogId;
            } else {
                $notice = 'Try On mendukung atasan, bawahan, atau outfit yang berisi satu atasan dan satu bawahan.';
            }
        } elseif ($wardrobeId !== null) {
            if (collect($wardrobeItems)->contains('id', $wardrobeId)) {
                $selected ??= 'wardrobe:'.$wardrobeId;
            } else {
                $notice = 'Try On mendukung atasan dan bawahan dari Wardrobe.';
            }
        } elseif ($outfitId !== null) {
            if (collect($outfits)->contains('id', $outfitId)) {
                $selected ??= 'outfit:'.$outfitId;
            } else {
                $notice = 'Outfit harus berisi tepat satu atasan dan satu bawahan agar dapat dipakai untuk Try On.';
            }
        }

        return view('try-on.index', [
            'catalogItems' => $catalogItems,
            'wardrobeItems' => $wardrobeItems,
            'outfits' => $outfits,
            'selected' => is_string($selected) ? $selected : null,
            'notice' => $notice,
            'result' => $result,
        ]);
    }

    public function generate(GenerateTryOnRequest $request, FastApiClient $api) // POST /try-on/generate (multipart)
    {
        [$wardrobeId, $catalogId, $outfitId] = $request->selectedIds();

        try {
            $result = $api->generateTryOn($request->file('person_image'), $wardrobeId, $catalogId, $outfitId);
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
            'generation_id' => $result['generation_id'],
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
