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
    protected static function createBatch(array $params = []): array
    {
        $code = 200;
        $msg = '添加成功！';
        if(!$re = self::create($params))
        {
            $code   = 500;
            $msg    = '添加失败!';
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
    protected static function detail(array $where = []): array
    {
        $code = 200;
        $data = '';
        if (!$re = self::where($where)->first())
        {
            $code = 500;
            $data = '暂无数据!';
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
    protected static function modify(array $where = [], array $params = []): array
    {
        $code = 200;
        $msg = '修改成功！';
        if(!$re = self::where($where)->update($params))
        {
            $code   = 500;
            $msg    = '修改失败!';
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
    public function updateBatch(array $multipleData = []): array
    {
        $code = 200;
        $msg = '更新成功';

        DB::beginTransaction();
        try {
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

    /**
     * Notes: 使用作用域扩展 Builder 链式操作
     * Date: 2019/4/29 13:18
     * @param $query
     * @param array $map
     * @return mixed
     *
     *  示例:
     * $map = [
     *     'id' => 1,
     *     'id' => ['in', [1,2,3]],
     *     'id' => ['or', 22],
     *     'id' => ['<>', 9],
     * ]
     */
    public function scopeWhereMap($query, array $map = [])
    {
        // 如果是空直接返回
        if (empty($map)) {
            return $query;
        }

        // 判断各种方法
        foreach ($map as $k => $v) {
            if (is_array($v)) {
                $sign = strtolower(current($v));
                switch ($sign) {
                    case 'in':
                        $query->whereIn($k, last($v));
                        break;
                    case 'or':
                        $query->orWhere($k, last($v));
                        break;
                    case 'notin':
                        $query->whereNotIn($k, last($v));
                        break;
                    case 'between':
                        $query->whereBetween($k, last($v));
                        break;
                    case 'notbetween':
                        $query->whereNotBetween($k, last($v));
                        break;
                    case 'null':
                        $query->whereNull($k);
                        break;
                    case 'notnull':
                        $query->whereNotNull($k);
                        break;
                    case '=':
                    case '>':
                    case '<':
                    case '<>':
                        $query->where($k, $sign, last($v));
                        break;
                    case 'like':
                        $query->where($k, $sign, '%'.last($v).'%');
                        break;
                    default:
                        $query;
                        break;
                }
            } else {
                $v ? $query->where($k, $v) : $query;
            }
        }
        return $query;
    }
}