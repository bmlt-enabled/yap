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
        $stmt = "UPDATE `users` SET `name` = :name";
        if (strlen($data->password) > 0) {
            $stmt = $stmt . ", `password` = SHA2(:password, 256)";
        }

        if ($role === 'admin') {
            $stmt = $stmt . ", `username` = :username, `service_bodies` = :service_bodies";
        }

        $stmt = $stmt . ", `permissions` = :permissions where `id` = :id";
        $db = new Database();
        $db->query($stmt);
        $db->bind(':id', $data->id);
        $db->bind(':name', $data->name);
        $db->bind(':permissions', array_sum($data->permissions));

        if (strlen($data->password) > 0) {
            $db->bind(':password', $data->password);
        }

        if ($role === 'admin') {
            $db->bind(':username', $data->username);
            $db->bind(':service_bodies', implode(",", $data->service_bodies));
        }

        $db->execute();
        $db->close();
    }

    public function saveUser($data)
    {
        $db = new Database();
        $stmt = "INSERT INTO `users` (`name`, `username`, `password`, `permissions`, `service_bodies`, `is_admin`) VALUES (:name, :username, SHA2(:password, 256), :permissions, :service_bodies, 0)";
        $db->query($stmt);
        $db->bind(':name', $data->name);
        $db->bind(':username', $data->username);
        $db->bind(':password', $data->password);
        $db->bind(":permissions", array_sum($data->permissions));
        $db->bind(':service_bodies', implode(",", $data->service_bodies));
        $db->execute();
        $db->close();
    }
}
