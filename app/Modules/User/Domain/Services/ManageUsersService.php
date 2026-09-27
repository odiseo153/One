<?php

namespace App\Modules\User\Domain\Services;

use App\Models\User;
use App\Modules\User\Adapters\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;

class ManageUsersService
{
    public function __construct(private readonly UserRepository $repository) {}

    public function index(?string $search, string $status): array
    {
        return $this->repository->getManagementData($search, $status);
    }

    public function create(array $data): User
    {
        $data['password'] = Hash::make($data['password']);

        return $this->repository->create($data);
    }

    public function update(User $user, array $data): void
    {
        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        $this->repository->update($user->id, $data);
    }

    public function delete(User $user): void
    {
        $this->repository->delete($user->id);
    }
}
