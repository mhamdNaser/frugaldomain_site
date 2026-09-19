<?php

namespace App\Modules\Component\Repositories\Interfaces;

interface ComponentCategoryRepositoryInterface
{
    public function all($search = null, $rowsPerPage = 10, $page = 1);

    public function active();

    public function find(int $id);

    public function create(array $data);

    public function update(int $id, array $data);

    public function delete($id);
}
