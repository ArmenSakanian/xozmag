// src/composables/useCategories.ts
export type FlatCategory = {
  id: number | string;
  name: string;
  slug?: string | null;
  code: string | number;
  parent: string | null;
  photo?: string | null;
};

const CATS_URL = "/api/admin/product/get_categories_flat.php";

// общий кеш на всё приложение
let _catsCache: FlatCategory[] | null = null;
let _catsPromise: Promise<FlatCategory[]> | null = null;

function normalizeCat(c: any): FlatCategory {
  const pid = c?.parent_id ?? c?.parent ?? null;
  const parent =
    pid === null ||
    pid === undefined ||
    String(pid) === "0" ||
    String(pid) === ""
      ? null
      : String(pid);

  return {
    id: c.id,
    name: c.name,
    slug: c.slug ?? null,
    code: c.code,
    parent,
    photo:
      c.photo_url_abs ||
      c.photo_url ||
      c.photo ||
      (c.photo_categories
        ? `/photo_categories_vitrina/${c.photo_categories}`
        : null),
  };
}

export async function getCategoriesOnce(): Promise<FlatCategory[]> {
  if (_catsCache) return _catsCache;
  if (_catsPromise) return _catsPromise;

  _catsPromise = fetch(CATS_URL, { headers: { Accept: "application/json" } })
    .then((r) => r.json())
    .then((data) => {
      const list = Array.isArray(data)
        ? data
        : data.categories || data.items || data.data || [];
      _catsCache = (list || []).filter(Boolean).map(normalizeCat);
      return _catsCache;
    })
    .catch(() => {
      _catsCache = [];
      return _catsCache;
    })
    .finally(() => {
      _catsPromise = null;
    });

  return _catsPromise;
}

// опционально (иногда удобно для админки)
export function resetCategoriesCache() {
  _catsCache = null;
  _catsPromise = null;
}