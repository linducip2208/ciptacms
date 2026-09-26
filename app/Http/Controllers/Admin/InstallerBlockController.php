<?php
namespace App\Http\Controllers\Admin;
use Illuminate\Http\Request;
class InstallerBlockController extends AdminController {
    public function __invoke(){ return view('admin.stub',['title'=>'Module','msg'=>'Managed by Module Engine.']); }
}
