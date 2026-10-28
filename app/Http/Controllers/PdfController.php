<?php

namespace App\Http\Controllers;


use App\Models\Ebook;
use Illuminate\Support\Facades\Storage;


class PdfController extends Controller
{


public function sample(Ebook $ebook)
{


if(!$ebook->file_path){

abort(404);

}



$file = storage_path(
'app/public/'.$ebook->file_path
);



if(!file_exists($file)){

abort(404);

}


// ส่ง PDF ตัวจริงก่อน
// ขั้นแรกใช้ preview ก่อน
// จำกัดหน้าจะเพิ่มด้วย PDF library ภายหลัง


return response()->file(

$file,

[

'Content-Disposition'=>'inline'

]

);


}


}