<?php
class LogicalDeleteBehavior extends ModelBehavior { 
    var $settings = array();
    
    //-------------------------------------------------------------
    // それぞれのモデルで上書きできるメンバ変数
    //-------------------------------------------------------------
    // delete flagのカラム名
    private $deleteFlagName = 'delete_flag';
    // deleteフラグをfind時にチェックするか(true:チェックする、false：チェックしない)
    private $isDeleteFlag = true;
    //-------------------------------------------------------------
    
    function setup(&$model, $config = array()) { 
        $this->settings = $config;
    }
    
    /**
     * find前処理
     * 
     * @access public
     * @author saku
     */
    function beforeFind(&$model, $query){	
        // findにdelete flagを自動で付加する
        $query = $this->_findDeleteFlag($model, $query);
        
        return $query;
    }
    
    
	/**
	 * 削除前処理
	 * 
	 * @access public
	 * @author saku
	 */
    function beforeDelete(&$model, $cascade = true)
    {
        if(isset($model->isDeleteFlag) && is_bool($model->isDeleteFlag)){
            // フラグの変更
            $this->isDeleteFlag = $model->isDeleteFlag;
        }
        
        if($this->isDeleteFlag === true){
            // 論理削除実行
            $this->_logicalDelete($model);
            return false;
        }else{
            // 物理削除実行
            return true;
        }
    }
    
    /**
     * findの検索条件に自動的にDeleteフラグを設定する
     * 
     * @access private
     * @author saku
     */
    private function _findDeleteFlag(&$model, $query){
        if(isset($model->isDeleteFlag) && is_bool($model->isDeleteFlag)){
            $this->isDeleteFlag = $model->isDeleteFlag;
        }
        if($this->isDeleteFlag === false){
            return $query;
        }

        $modelName = $model->name;		
        if(isset($model->deleteFlagName)){
            // delete flagのカラム名の変更
            $this->deleteFlagName = $model->deleteFlagName;
        }

        // 論理削除カラム名
        $name = sprintf("%s.%s", $modelName, $this->deleteFlagName);
        
        if(empty($query['conditions'])){
            // conditionsに何も設定されていない
            $query['conditions'] = array($name => false);
        }else if(is_array($query['conditions'])){
            // conditions が配列で書かれている
            $bufCond = $query['conditions'];
            $deleteConditions = array($name=>false);
            $query['conditions'] = Set::merge($bufCond, $deleteConditions);
        }else if(is_string($query['conditions'])){
            // conditions が文字列で書かれている
            $bufCond = $query['conditions'];
            $query['conditions'] = array($name=>false, $bufCond);
        }else{
            // とりあえず何もない？
        }

        return $query;
    }
    
    /**
     * deleteフラグを立てる
     * ※コールバック呼ばれない
     *
     * @access private
     * @author saku
     */
    private function _logicalDelete(&$model){
        if(empty($model->id) || !is_numeric($model->id)){
            return false;
        }
        
        if(isset($model->deleteFlagName)){
            // delete flagのカラム名の変更
            $this->deleteFlagName = $model->deleteFlagName;
        }

        $model->saveField($this->deleteFlagName, true, array('callbacks'=>false));
        
        return true;
    }
} 
?>