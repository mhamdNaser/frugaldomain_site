<?php

namespace App\Modules\Component\Repositories\Interfaces;

interface ComponentRepositoryInterface
{
    public function all($search = null, $rowsPerPage = 10, $page = 1, array $filters = []);

    public function published(array $filters = []);

    public function find(int $id);

    public function findBySlug(string $slug, bool $publishedOnly = true);

    public function create(array $data);

    public function update(int $id, array $data);

    public function delete($id);

    public function toggleStatus(int $id);

    public function storeFile(int $id, $file);

    public function removeFile(int $id);

    public function registerDownload(int $id);
}
