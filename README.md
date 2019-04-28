这个扩展包用来批量更新数据

## User

    $data = [
        ['id' => 2, 'title' => '我们的试管', 'author' => '我们的试管', 'status' => 1],
        ['title' => '我们的试2222222222管', 'author' => '我们的试管', 'status' => 1],
    ];
    $old_ids = Artivity::pluck('id')->toArray();
    $ids = array_column($data, 'id');
    $diff_ids = array_diff($old_ids, $ids);

    $updateData = $createData = [];
    foreach ($data as $v){
        if(isset($v['id']) && $v['id'])
            $updateData[] = $v;
        else
            $createData[] = $v;
    }

    $result = (new Artivity())->updateBatch($updateData);

    if($result['code'] == 200){
        Artivity::insert($createData);
        Artivity::destroy($diff_ids);
    }
        dd($result['msg']);

