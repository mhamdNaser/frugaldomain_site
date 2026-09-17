<?php

namespace App\Modules\App\Repositories\Interfaces;

interface AppRepositoryInterface
{
    /** Admin listing: every app, drafts included. */
    public function all($search = null, $rowsPerPage = 10, $page = 1);

    /** Public listing: published apps only. */
    public function published();

    public function find(int $id);

    public function findBySlug(string $slug, bool $publishedOnly = true);

    public function create(array $data);

    public function update(int $id, array $data);

    public function delete($id);

    public function toggleStatus(int $id);
}
