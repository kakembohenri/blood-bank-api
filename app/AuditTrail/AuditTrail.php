<?php

namespace App\AuditTrail;

class AuditTrail
{

    protected $table;

    public function __construct($table)
    {
        $this->table = $table;
    }

    public static function UserForeignKeys($table)
    {
        $table->timestamp("created_at");
        $table->foreignId("created_by")->constrained("users");
        $table->timestamp("modified_at")->nullable();
        $table->foreignId("modified_by")->nullable()->constrained("users");
    }

    public static function NullableCreatedBy($table)
    {
        $table->timestamp("created_at");
        $table->foreignId("created_by")->nullable()->constrained("users");
        $table->timestamp("modified_at")->nullable();
        $table->foreignId("modified_by")->nullable()->constrained("users");
    }
}
