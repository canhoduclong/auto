<?php
namespace App\Support;
use App\Models\User;
class TaskWorkspace {
    public static function layout(User $user): string {
        $names=$user->roles->pluck('name')->map(fn($r)=>strtolower($r))->all();
        $role=strtolower((string)session('active_role',''));
        $map=['admin'=>'admin','ceo'=>'ceo','director'=>'director','account'=>'accounting','accountant'=>'accounting','accounting'=>'accounting','warehouse'=>'warehouse','package'=>'package','shipper'=>'shipper','ship'=>'shipper','manager_shipper'=>'shipper','sale'=>'site','leader'=>'site','manager'=>'site','sale_manager'=>'site'];
        if(in_array($role,$names,true) && isset($map[$role]))return 'layouts.'.$map[$role];
        foreach($map as $name=>$layout)if(in_array($name,$names,true))return 'layouts.'.$layout;
        return 'layouts.site';
    }
}
