<?php echo '<?php' . PHP_EOL; ?>

namespace App\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\{{$name}};
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

{{$comment}}
class {{$className}} extends AdminBaseController
{

    /**
    * Index page
    * @uri {{$routePrefix}}/{{$table}}/index
    * @throw NotFoundHttpException
    * @return View
    */
    public function index()
    {
        $title = '{{$name}}';
        $component = '{{$ucTable}}Index';
        return vue(compact('title', 'component'));
    }

    /**
    * Create new entry
    * @uri {{$routePrefix}}/{{$table}}/create
    * @throw NotFoundHttpException
    * @return View
    */
    public function create (Request $req)
    {
        $component = '{{$ucTable}}Form';
        $title = 'Create {{$table}}';
        return vue(compact('title', 'component'));
    }

    /**
    * @uri {{$routePrefix}}/{{$table}}/edit?id=$id
    * @throw NotFoundHttpException
    * @return View
    */
    public function edit (Request $req)
    {
        $id = $req->id;
        $entry = {{$name}}::find($id);

        if (!$entry) {
            throw new NotFoundHttpException();
        }

        /**
        * @var {{$name}} $entry
        */
        $jsonData = compact('entry');
        $title = 'Edit';
        $component = '{{$ucTable}}Form';

        return vue(compact('title', 'component'), $jsonData);
    }

    /**
    * @uri {{$routePrefix}}/{{$table}}/remove
    * @return array
    */
    public function remove(Request $req)
    {
        $id = $req->id;
        $entry = {{$name}}::find($id);

        if (!$entry) {
            throw new NotFoundHttpException();
        }

        $entry->delete();

        return [
            'code' => 0,
            'message' => 'Đã xóa'
        ];
    }

    /**
    * @uri {{$routePrefix}}/{{$table}}/save
    * @return array
    */
    public function save(Request $req)
    {
        if (!$req->isMethod('POST')) {
            return ['code' => 405, 'message' => 'Method not allow'];
        }

        $data = $req->get('entry');

        $rules = {!! $rules !!};

        $v = Validator::make($data, $rules);

        if ($v->fails()) {
            return [
                'code' => 2,
                'errors' => $v->errors()
            ];
        }

        /**
        * @var {{$name}} $entry
        */
        if (isset($data['id'])) {
            $entry = {{$name}}::find($data['id']);
            if (!$entry) {
                return [
                    'code' => 3,
                    'message' => 'Không tìm thấy',
                ];
            }

            $entry->fill($data);
            $entry->save();

            return [
                'code' => 0,
                'message' => 'Đã cập nhật',
                'id' => $entry->id
            ];
        } else {
            $entry = new {{$name}}();
            $entry->fill($data);
            $entry->save();

            return [
                'code' => 0,
                'message' => 'Đã thêm',
                'id' => $entry->id
            ];
        }
    }

    /**
    * @param Request $req
    */
    public function toggleStatus(Request $req)
    {
        $id = $req->get('id');
        $entry = {{$name}}::find($id);

        if (!$id) {
            return [
                'code' => 404,
                'message' => 'Not Found'
            ];
        }

        $entry->status = $req->status ? 1 : 0;
        $entry->save();

        return [
            'code' => 200,
            'message' => 'Đã lưu'
        ];
    }

    /**
    * Ajax data for index page
    * @uri {{$routePrefix}}/{{$table}}/data
    * @return array
    */
    public function data(Request $req)
    {
        $query = {{$name}}::query()->orderBy('id', 'desc');

        if ($req->keyword) {
            //$query->where('title', 'LIKE', '%' . $req->keyword. '%');
        }

        $query->createdIn($req->created);

        $entries = $query->paginate();

        return [
            'code' => 0,
            'data' => $entries->items(),
            'paginate' => [
                'currentPage' => $entries->currentPage(),
                'lastPage' => $entries->lastPage(),
            ]
        ];
    }

    public function export()
    {
        <?php $alphas = range('A', 'Z');?>
        $keys = [
        @for($i = 0; $i < 26; $i++)
            <?php if (!isset($fields[$i])) break; ?>
        '{{$fields[$i]}}' => ['{{$alphas[$i]}}', '{{$fields[$i]}}'],
        @endfor
        ];

        $query = {{$name}}::query()->orderBy('id', 'desc');

        $entries = $query->paginate();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($keys as $key => $v) {
            if (is_string($v)) {
                $sheet->setCellValue($v . "1", $key);
            } elseif (is_array($v)) {
                list($c, $n) = $v;
                 $sheet->setCellValue($c . "1", $n);
            }
        }

        foreach ($entries as $index => $entry) {
            $idx = $index + 2;
            foreach ($keys as $key => $v) {
                if (is_string($v)) {
                    $sheet->setCellValue("$v$idx", data_get($entry->toArray(), $key));
                } elseif (is_array($v)) {
                    list($c, $n) = $v;
                    $sheet->setCellValue("$c$idx", data_get($entry->toArray(), $key));
                }
            }
        }
        $writer = new Xlsx($spreadsheet);
        // We'll be outputting an excel file
        header('Content-type: application/vnd.ms-excel');
        $filename = uniqid() . '-' . date('Y_m_d H_i') . ".xlsx";

        // It will be called file.xls
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        // Write file to the browser
        $writer->save('php://output');
        die;
    }
}
