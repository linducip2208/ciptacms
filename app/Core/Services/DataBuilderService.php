<?php
namespace App\Core\Services;
use App\Models\ContentType;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
class DataBuilderService {
    public function fieldMap(string $type): string {
        return match($type){
            'number','currency'=>'decimal','boolean'=>'boolean','date'=>'date','datetime'=>'dateTime',
            'file','image','select','multi-select','relation','repeater','json'=>'json',
            'uuid'=>'uuid','slug'=>'string', default=>'text',
        };
    }
    public function createPhysicalTable(ContentType $ct): void {
        $table = $ct->table_name ?: 'cb_'.strtolower(preg_replace('/[^a-z0-9]+/i','_',$ct->slug));
        if(Schema::hasTable($table)) return;
        Schema::create($table, function(Blueprint $t) use ($ct){
            $t->id(); $t->uuid('uuid')->nullable()->unique();
            $t->string('tenant_id')->nullable()->index();
            foreach(($ct->fields??[]) as $f){
                $col = $f['slug'] ?? $f['name'] ?? null; if(!$col) continue;
                $mapped = $this->fieldMap($f['type']??'text');
                try {
                    if($mapped==='decimal') $t->decimal($col,15,2)->nullable();
                    elseif($mapped==='boolean') $t->boolean($col)->default(false);
                    elseif($mapped==='json') $t->json($col)->nullable();
                    elseif($mapped==='uuid') $t->uuid($col)->nullable();
                    elseif(in_array($mapped,['date','dateTime'])) $t->$mapped($col)->nullable();
                    else $t->text($col)->nullable();
                } catch(\Throwable $e){}
            }
            $t->timestamps(); $t->softDeletes();
        });
        $ct->update(['table_name'=>$table]);
    }
}
