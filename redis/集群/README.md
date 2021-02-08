# Demo：docker-compose 实现redis集群

## 容器对应关系

|  容器名称   | IP  | 客户端连接端口映射 | 集群端口映射  | 预想角色  |
|  ----  | ----  |  ----  | ----  | ----  |
| redis-c1  | 172.31.0.11 | 6301->6379 | 16301->16379  | master |
| redis-c2  | 172.31.0.12 | 6302->6379 | 16302->16379  | master |
| redis-c3  | 172.31.0.13 | 6303->6379 | 16303->16379  | master |
| redis-c4  | 172.31.0.14 | 6304->6379 | 16304->16379  | slave |
| redis-c5  | 172.31.0.15 | 6305->6379 | 16305->16379  | slave |
| redis-c6  | 172.31.0.16 | 6306->6379 | 16306->16379  | slave |

![](http://img.github.mailjob.net/jefferyjob.github.io/20210208133634.png)

## 在宿主机 /data 上传 j_cluster

> 如果上传到了其他目录需要更改 yml 里面的数据卷映射条件

## 启动项目

```
# 进入到项目目录
cd /data/j_cluster

# 启动项目
docker-compose up -d
```

![](http://img.github.mailjob.net/jefferyjob.github.io/20210208132002.png)

## 这里以进入 redis-c1 为例

```
docker exec -it redis-c1 /bin/bash
```

## 在容器内打开redis命令客户端 

```
redis-cli
```

## 执行组建集群命令（请根据自己的ip信息进行拼接）

```
redis-cli --cluster create 172.17.0.2:6379  172.17.0.3:6379  172.17.0.4:6379 --cluster-replicas 0
```


## 图片

