<?php echo '<?php' . PHP_EOL; ?>

namespace App\Models;
{{$comment}}

{{$phpdoc}}
class {{$name}} extends BaseModel
{
    protected $table = '{{$table}}';
    protected $fillable = {!! $fillable !!};
}
