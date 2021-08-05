# light-office
基于maatwebsite/excel的excel操作封装

#使用方法
* 将项目中的Export下的Helper及SmartExport文件复制到laravel项目的对应位置
* 在导出时实例化$export对象时采用SmartExport或者SmartExport的子类
* 传入数据源（数组，集合，DB实例，Model实例，stdClass）均可，表头，回调函数或者null
* 分页导出量级较小建议直接查出数据，以数组数据源形式传入
* 必要时可以写回调函数，到处前对数据进行预处理