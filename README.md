# 介绍

 超级轻量级php框架，简单支持swoole环境

# 特点

- 轻
  - 没有大型框架的很多难以用上的功能
  - 只针对api做开发
- 小
  - 核心文件只有近100k（未压缩）
  - 因为小所以快，fpm模式查库+查reids只消耗0.15M内存;耗时0.003s（毫秒级）;
  - 性能测试结果
    - 命令 `ab -c 100 -n 5000 -k http://127.0.0.1:9599/xxx`
    - fpm环境 结果 300+qps
    - swoole环境 结果 2400+qps
  - 上述测试机为1h1g共享机型
- 易
  - 简单语法，会php就会写
  - 简单实现了url定位到控制器，输出json格式数据
## 安装
  composer create-project easyphp/easy your-project-name 

## 环境要求

```json
{
    "php": ">=7.0.0",
    "ext-pdo": "*",
    "ext-redis": "*"
}
```
## 文档地址
[传送门](https://doc.easy-php.cn/)

## 搭建的后台框架
[传送门](https://github.com/ljk123/easy-admin-demo)
