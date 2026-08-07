<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['id' => 1, 'parent_id' => null, 'name' => 'Electronics', 'slug' => 'electronics', 'description' => 'Gadgets, phones, laptops and more', 'image' => 'global/uploads/category/electronics.png', 'order' => 1, 'status' => 1, 'is_trending' => 1, 'in_landing_hero' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'parent_id' => 1, 'name' => 'Smartphones', 'slug' => 'smartphones', 'description' => 'Latest smartphones and accessories', 'image' => 'global/uploads/category/smartphones.png', 'order' => 1, 'status' => 1, 'is_trending' => 1, 'in_landing_hero' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'parent_id' => 1, 'name' => 'Laptops', 'slug' => 'laptops', 'description' => 'Powerful laptops for work and gaming', 'image' => 'global/uploads/category/laptops.png', 'order' => 2, 'status' => 1, 'is_trending' => 1, 'in_landing_hero' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'parent_id' => 1, 'name' => 'Audio', 'slug' => 'audio', 'description' => 'Headphones, speakers and sound gear', 'image' => 'global/uploads/category/audio.png', 'order' => 3, 'status' => 1, 'is_trending' => 0, 'in_landing_hero' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'parent_id' => null, 'name' => 'Fashion', 'slug' => 'fashion', 'description' => 'Clothing, shoes and fashion accessories', 'image' => 'global/uploads/category/fashion.png', 'order' => 2, 'status' => 1, 'is_trending' => 1, 'in_landing_hero' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'parent_id' => 5, 'name' => "Men's Clothing", 'slug' => 'mens-clothing', 'description' => 'Stylish men apparel', 'image' => 'global/uploads/category/mens-clothing.png', 'order' => 1, 'status' => 1, 'is_trending' => 1, 'in_landing_hero' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'parent_id' => 5, 'name' => "Women's Clothing", 'slug' => 'womens-clothing', 'description' => 'Trendy women apparel', 'image' => 'global/uploads/category/womens-clothing.png', 'order' => 2, 'status' => 1, 'is_trending' => 1, 'in_landing_hero' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 8, 'parent_id' => null, 'name' => 'Home & Garden', 'slug' => 'home-garden', 'description' => 'Furniture, decor and garden tools', 'image' => 'global/uploads/category/home-garden.png', 'order' => 3, 'status' => 1, 'is_trending' => 0, 'in_landing_hero' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 9, 'parent_id' => 8, 'name' => 'Furniture', 'slug' => 'furniture', 'description' => 'Chairs, tables, sofas and more', 'image' => 'global/uploads/category/furniture.png', 'order' => 1, 'status' => 1, 'is_trending' => 0, 'in_landing_hero' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 10, 'parent_id' => null, 'name' => 'Digital Products', 'slug' => 'digital-products', 'description' => 'Software, ebooks and online courses', 'image' => 'global/uploads/category/digital-products.png', 'order' => 4, 'status' => 1, 'is_trending' => 1, 'in_landing_hero' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 11, 'parent_id' => 10, 'name' => 'Ebooks', 'slug' => 'ebooks', 'description' => 'Digital books and guides', 'image' => 'global/uploads/category/ebooks.png', 'order' => 1, 'status' => 1, 'is_trending' => 0, 'in_landing_hero' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 12, 'parent_id' => null, 'name' => 'Services', 'slug' => 'services', 'description' => 'Freelance and professional services', 'image' => 'global/uploads/category/services.png', 'order' => 5, 'status' => 1, 'is_trending' => 0, 'in_landing_hero' => 0, 'created_at' => now(), 'updated_at' => now()],
        ];

        foreach ($categories as $category) {
            DB::table('categories')->updateOrInsert(
                ['id' => $category['id']],
                $category
            );
        }
    }
}
