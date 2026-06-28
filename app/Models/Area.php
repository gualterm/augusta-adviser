<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Area extends Model {
    protected $fillable = ["name"];
    public function employees() { return $this->belongsToMany(Employee::class, "employee_area"); }
    public function services()  { return $this->belongsToMany(Service::class, "service_area"); }
}
