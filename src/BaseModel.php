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
    /**
     * Notes: 批量更新
     * Date: 2019/4/26 13:52
     * @param array $multipleData
     * @return bool
     */
    public function updateBatch($multipleData = [])
    {
        DB::beginTransaction();
        try {
            if (empty($multipleData)) {
                throw new \Exception("数据不能为空");
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

            return [
                'code'  => 200,
                'msg'   => '更新成功'
            ];
        } catch (\Exception $e) {
            DB::rollback();
            return [
                'code'  => 500,
                'msg'   => $e->getMessage()
            ];
        }
    }
}