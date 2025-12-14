<template>
  <div class="admin-panel">
    <h1>Админ-панель</h1>

    <div class="tiles">
      <router-link to="/admin/categories" class="tile">Категории</router-link>
      <router-link to="/admin/attributes" class="tile">Характеристики</router-link>
      <router-link to="/admin/products" class="tile">Товары</router-link>
      <router-link to="/admin/addproducts" class="tile">Добавить товары</router-link>
    </div>
  </div>
</template>

<style>
.admin-panel { padding: 20px; }
.tiles { display: flex; gap: 20px; }
.tile {
  padding: 25px;
  background: #26282c;
  border-radius: 12px;
  color: white;
  text-decoration: none;
  font-size: 18px;
  width: 200px;
  text-align: center;
  transition: .2s;
}
.tile:hover { background: #33363b; }
</style>
