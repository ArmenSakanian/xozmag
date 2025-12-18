<template>
  <div class="admin-panel">
    <h1>Админ-панель</h1>

    <div class="tiles">
      <router-link to="/admin/categories" class="tile">Категории</router-link>
      <router-link to="/admin/attributes" class="tile">Характеристики</router-link>
      <router-link to="/admin/products" class="tile">Товары</router-link>
      <router-link to="/admin/addproducts" class="tile">Добавить товары</router-link>
      <router-link to="/admin/convert" class="tile">Конвертировать изображение</router-link>
      <router-link to="/admin/order" class="tile">Заказ поставщику</router-link>
      <router-link to="/admin/minstock" class="tile">Минимальный остаток</router-link>


    </div>
  </div>
</template>

<style>
.admin-panel { padding: 20px; }
.tiles { display: flex; gap: 20px; }
.tile {
  padding: 25px;
  margin-top: 30px;
  background: #26282c;
  border-radius: 12px;
  color: white;
  text-decoration: none;
  font-size: 18px;
  width: 200px;
  text-align: center;
  transition: .2s;
}
.tile:hover { 
  background: #33363b; 
}

@media (max-width: 768px) {
  .tiles {
    display: flex;
    flex-direction: column;
    align-items: center;
  }
}
</style>
