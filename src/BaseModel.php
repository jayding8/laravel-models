<?php
/**
 * Created by PhpStorm.
 * User: james.xue
 * Date: 2019/4/26
 * Time: 13:49
 */
namespace James\Models;

use \Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BaseModel extends Model
{
    protected $guarded = [];

    /**
     * Notes: 新增
     * Date: 2019/4/28 15:43
     * @param array $params
     * @return array
     */
    protected static function createBatch($params = []): array
    {
        $code = 200;
        $msg = '添加成功！';
        if(!is_array($params) || empty($params))
        {
            $code   = 500;
            $msg    = '参数格式错误!';
        }else{
            if(!$re = self::create($params))
            {
                $code   = 500;
                $msg    = '添加失败!';
            }
        }

        return [
            'code'  => $code,
            'data'  => $msg,
        ];
    }

    /**
     * Notes: 详情
     * Date: 2019/4/28 14:52
     * @param $id
     * @return Model|BaseModel|null
     */
    protected static function detail($where = []): array
    {
        $code = 200;
        $data = '';
        if(!is_array($where) || empty($where))
        {
            $code   = 500;
            $data    = '参数格式错误!';
        }else {
            if (!$re = self::where($where)->first()) {
                $code = 500;
                $data = '暂无数据!';
            }
        }

        return [
            'code'  => $code,
            'data'  => $data ? $data : $re,
        ];
    }

    /**
     * Notes: 修改
     * Date: 2019/4/28 15:55
     * @param string $id
     * @param $params
     * @return array
     */
    protected static function modify($where = [], $params = []): array
    {
        $code = 200;
        $msg = '修改成功！';
        if(!is_array($params) || !is_array($where) || empty($where) || empty($params))
        {
            $code   = 500;
            $msg    = '参数格式错误!';
        }else{
            if(!$re = self::where($where)->update($params))
            {
                $code   = 500;
                $msg    = '修改失败!';
            }
        }

        return [
            'code'  => $code,
            'data'  => $msg,
        ];
    }

    /**
     * Notes: 删除
     * Date: 2019/4/28 14:58
     * @param $id
     * @return Model|BaseModel|null
     */
    protected static function del($id = ''): array
    {
        $code = 200;
        $msg = '删除成功';
        if(!self::where('id', $id)->delete())
        {
            $code = 500;
            $msg = '删除失败';
        }

        return [
            'code'  => $code,
            'msg'   => $msg,
        ];
    }

    /**
     * Notes: 批量更新
     * Date: 2019/4/26 13:52
     * @param array $multipleData
     * @return bool
     */
    public function updateBatch($multipleData = []): array
    {
        $code = 200;
        $msg = '更新成功';

        DB::beginTransaction();
        try {
            if (empty($multipleData) || !is_array($multipleData)) {
                throw new \Exception("数据格式错误");
            }
            $tableName = DB::getTablePrefix() . $this->getTable(); // 表名
            $firstRow = current($multipleData);

            $updateColumn = array_keys($firstRow);
            // 默认以id为条件更新，如果没有ID则以第一个字段为条件
            $referenceColumn = isset($firstRow['id']) ? 'id' : current($updateColumn);
            unset($updateColumn[0]);
            // 拼接sql语句
            $updateSql = "UPDATE " . $tableName . " SET ";
            $sets  = [];
            $bindings = [];
            foreach ($updateColumn as $uColumn) {
                $setSql = "`" . $uColumn . "` = CASE ";
                foreach ($multipleData as $data) {
                    $setSql .= "WHEN `" . $referenceColumn . "` = ? THEN ? ";
                    $bindings[] = $data[$referenceColumn];
                    $bindings[] = $data[$uColumn];
                }
                $setSql .= "ELSE `" . $uColumn . "` END ";
                $sets[] = $setSql;
            }
            $updateSql .= implode(', ', $sets);
            $whereIn = collect($multipleData)->pluck($referenceColumn)->values()->all();
            $bindings = array_merge($bindings, $whereIn);
            $whereIn = rtrim(str_repeat('?,', count($whereIn)), ',');
            $updateSql = rtrim($updateSql, ", ") . " WHERE `" . $referenceColumn . "` IN (" . $whereIn . ")";

            // 传入预处理sql语句和对应绑定数据
            DB::update($updateSql, $bindings);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            $code = 500;
            $msg = $e->getMessage();
        }

        return [
            'code'  => $code,
            'msg'   => $msg
        ];
    }
}