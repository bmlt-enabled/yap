<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class UserRepository
{
    public function getUsers($service_bodies = null)
    {
        if ($service_bodies === null) {
            return DB::select("SELECT id, name, username, is_admin, permissions, service_bodies, created_on FROM `users`");
        } else {
            if (count($service_bodies) > 0) {
                $query = "SELECT id, name, username, permissions, service_bodies, created_on FROM `users` WHERE";
                $service_bodies_query = "";
                foreach ($service_bodies as $service_body) {
                    if ($service_bodies_query !== "") {
                        $service_bodies_query .= " OR";
                    }
                    $service_bodies_query .= " FIND_IN_SET('$service_body', `service_bodies`)";
                }
                $query .= "(" . $service_bodies_query . ") AND id <> " . $_SESSION['auth_id'];
                return DB::select($query);
            }
        }
    }

    public function deleteUser($id)
    {
        DB::delete("DELETE FROM `users` WHERE `id`= ?", [$id]);
    }

    public function editUser($data, $role)
    {
        $stmt = "UPDATE `users` SET `name` = ?";
        $vars = [$data->name];

        if (strlen($data->password) > 0) {
            $stmt = $stmt . ", `password` = SHA2(?, 256)";
            array_push($vars, $data->password);
        }

        if ($role === 'admin') {
            $stmt = $stmt . ", `username` = ?, `service_bodies` = ?";
            array_push($vars, $data->username);
            array_push($vars, implode(",", $data->service_bodies));
        }

        $stmt = $stmt . ", `permissions` = ? where `id` = ?";
        array_push($vars, array_sum($data->permissions));
        array_push($vars, $data->id);

        DB::update($stmt, $vars);
    }
}
