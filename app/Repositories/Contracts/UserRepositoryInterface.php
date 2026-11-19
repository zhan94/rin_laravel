<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\DTOs\Users\UserData;
use App\DTOs\Users\UserFilterData;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, \App\Models\User>
     */
    public function paginate(UserFilterData $filters, User $actor): LengthAwarePaginator;

    /**
     * @return list<array{id:int,role_key:string,title:string,translations:array<string,string>,is_active:bool,sort_order:int}>
     */
    public function roleOptions(): array;

    public function create(UserData $data): User;

    public function update(User $user, UserData $data): User;

    public function delete(User $user): void;
}
