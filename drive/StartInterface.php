<?php 
/*
|--------------------------------------------------------------------------
| 预定义接口实现
|--------------------------------------------------------------------------
*/
interface StartInterface {

	/**
	 * [getConfig 获取配置信息]
	 * @author 		Shaowei Pu <pushaowei@sporte.cn>
	 * @CreateTime	2017-04-29T11:03:17+0800
	 * @return                              [type] [description]
	 */
	public function getConfig();

	/**
	 * [getContent 获取匹配内容]
	 * @author 		Shaowei Pu <pushaowei@sporte.cn>
	 * @CreateTime	2017-04-29T12:58:28+0800
	 * @return                              [type] [description]
	 */
	public function getContent($content);

	/**
	 * [InstertDatabase 数据入库]
	 * @author 		Shaowei Pu <pushaowei@sporte.cn>
	 * @CreateTime	2017-04-29T12:59:59+0800
	 */
	public function InstertDatabase(array $data );

	/**
	 * [initClient 初始化客户端]
	 * @author 		Shaowei Pu <pushaowei@sporte.cn>
	 * @CreateTime	2017-04-29T21:13:16+0800
	 * @return                              [type] [description]
	 */
	public function initClient();

	/**
	 * [displayUi 视图输出]
	 * @author 		Shaowei Pu <pushaowei@sporte.cn>
	 * @CreateTime	2017-04-29T21:16:33+0800
	 * @return                              [type] [description]
	 */
	public function displayUi();
}
