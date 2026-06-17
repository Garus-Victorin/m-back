<?php

use App\Models\Category;
use App\Models\User;

function adminAuthHeaders(User $user): array
{
    return [
        'Authorization' => 'Bearer '.$user->createToken('admin-test-suite')->plainTextToken,
    ];
}

it('allows an admin to create update list and delete categories', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'is_active' => true,
    ]);

    $createResponse = $this
        ->withHeaders(adminAuthHeaders($admin))
        ->postJson('/api/v1/admin/categories', [
            'name' => 'Informatique',
            'description' => 'Produits informatiques et accessoires',
            'is_active' => true,
        ]);

    $createResponse
        ->assertCreated()
        ->assertJsonPath('data.category.name', 'Informatique')
        ->assertJsonPath('data.category.slug', 'informatique');

    $category = Category::query()->firstOrFail();

    $this
        ->withHeaders(adminAuthHeaders($admin))
        ->getJson('/api/v1/admin/categories')
        ->assertOk()
        ->assertJsonCount(1, 'data.categories')
        ->assertJsonPath('data.categories.0.id', $category->id);

    $this
        ->withHeaders(adminAuthHeaders($admin))
        ->patchJson('/api/v1/admin/categories/'.$category->id, [
            'name' => 'Informatique Pro',
            'description' => 'Matériel premium',
            'is_active' => false,
        ])
        ->assertOk()
        ->assertJsonPath('data.category.slug', 'informatique-pro')
        ->assertJsonPath('data.category.is_active', false);

    $this
        ->withHeaders(adminAuthHeaders($admin))
        ->deleteJson('/api/v1/admin/categories/'.$category->id)
        ->assertOk()
        ->assertJsonPath('success', true);

    expect(Category::query()->count())->toBe(0);
});

it('forbids non admins from managing categories', function () {
    $seller = User::factory()->create([
        'role' => 'seller',
        'is_active' => true,
    ]);

    $category = Category::factory()->create();

    $this
        ->withHeaders(adminAuthHeaders($seller))
        ->postJson('/api/v1/admin/categories', [
            'name' => 'Forbidden Category',
        ])
        ->assertForbidden();

    $this
        ->withHeaders(adminAuthHeaders($seller))
        ->patchJson('/api/v1/admin/categories/'.$category->id, [
            'name' => 'Forbidden Update',
        ])
        ->assertForbidden();

    $this
        ->withHeaders(adminAuthHeaders($seller))
        ->deleteJson('/api/v1/admin/categories/'.$category->id)
        ->assertForbidden();
});
