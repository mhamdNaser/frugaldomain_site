<?php

namespace App\Modules\Icon\Repositories\Interfaces;

interface IconRepositoryInterface
{
    public function all($search = null, $rowsPerPage = 10, $page = 1);
    public function allWithoutPagination($search = null, $category = null, $style = null);
    public function paginatePublic($search = null, $category = null, $style = null, $perPage = 60, $page = 1);
    public function find(int $id);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete($id);
    public function toggleStatus(int $id);
    public function deleteArray(array $ids);

}
