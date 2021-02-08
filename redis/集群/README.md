# Demo：docker-compose 实现redis集群

### 容器对应关系

|  容器名称   | IP  | 客户端连接端口映射 | 集群端口映射  | 预想角色  |
|  ----  | ----  |  ----  | ----  | ----  |
| redis-c1  | 172.31.0.11 | 6301->6379 | 16301->16379  | master |
| redis-c2  | 172.31.0.12 | 6302->6379 | 16302->16379  | master |
| redis-c3  | 172.31.0.13 | 6303->6379 | 16303->16379  | master |
| redis-c4  | 172.31.0.14 | 6304->6379 | 16304->16379  | slave |
| redis-c5  | 172.31.0.15 | 6305->6379 | 16305->16379  | slave |
| redis-c6  | 172.31.0.16 | 6306->6379 | 16306->16379  | slave |

![](http://img.github.mailjob.net/jefferyjob.github.io/20210208133634.png)

### 在宿主机 /data 上传 j_cluster

> 如果上传到了其他目录需要更改 yml 里面的数据卷映射条件

### 启动项目

```
# 进入到项目目录
cd /data/j_cluster

# 启动项目
docker-compose up -d
```

![](http://img.github.mailjob.net/jefferyjob.github.io/20210208132002.png)

### 查看一下各个节点的ip

```
docker container inspect redis-c1 redis-c2 redis-c3 redis-c4 redis-c5 redis-c6 | grep IPv4Address
```

> 和我在 yam 中定义的ip一致

![](http://img.github.mailjob.net/jefferyjob.github.io/20210208134455.png)

# 开始搭建集群

### 这里以进入 redis-c1 为例

```
docker exec -it redis-c1 /bin/bash
```

### 浏览一下redis的集群命令

> 以下命令只有 redis5 以后才有,redis5 以后redis发布了集群搭建命令  
> redis5 以前如果你要搭建的话，可以采用 Ruby 脚本  

```
# 查看集群帮助文档
root@e4d19717bbed:/redis# redis-cli --cluster help

# 集群相关命令如下
Cluster Manager Commands:
  create         host1:port1 ... hostN:portN   #创建集群
                 --cluster-replicas <arg>      #从节点个数
  check          host:port                     #检查集群
                 --cluster-search-multiple-owners #检查是否有槽同时被分配给了多个节点
  info           host:port                     #查看集群状态
  fix            host:port                     #修复集群
                 --cluster-search-multiple-owners #修复槽的重复分配问题
  reshard        host:port                     #指定集群的任意一节点进行迁移slot，重新分slots
                 --cluster-from <arg>          #需要从哪些源节点上迁移slot，可从多个源节点完成迁移，以逗号隔开，传递的是节点的node id，还可以直接传递--from all，这样源节点就是集群的所有节点，不传递该参数的话，则会在迁移过程中提示用户输入
                 --cluster-to <arg>            #slot需要迁移的目的节点的node id，目的节点只能填写一个，不传递该参数的话，则会在迁移过程中提示用户输入
                 --cluster-slots <arg>         #需要迁移的slot数量，不传递该参数的话，则会在迁移过程中提示用户输入。
                 --cluster-yes                 #指定迁移时的确认输入
                 --cluster-timeout <arg>       #设置migrate命令的超时时间
                 --cluster-pipeline <arg>      #定义cluster getkeysinslot命令一次取出的key数量，不传的话使用默认值为10
                 --cluster-replace             #是否直接replace到目标节点
  rebalance      host:port                                      #指定集群的任意一节点进行平衡集群节点slot数量 
                 --cluster-weight <node1=w1...nodeN=wN>         #指定集群节点的权重
                 --cluster-use-empty-masters                    #设置可以让没有分配slot的主节点参与，默认不允许
                 --cluster-timeout <arg>                        #设置migrate命令的超时时间
                 --cluster-simulate                             #模拟rebalance操作，不会真正执行迁移操作
                 --cluster-pipeline <arg>                       #定义cluster getkeysinslot命令一次取出的key数量，默认值为10
                 --cluster-threshold <arg>                      #迁移的slot阈值超过threshold，执行rebalance操作
                 --cluster-replace                              #是否直接replace到目标节点
  add-node       new_host:new_port existing_host:existing_port  #添加节点，把新节点加入到指定的集群，默认添加主节点
                 --cluster-slave                                #新节点作为从节点，默认随机一个主节点
                 --cluster-master-id <arg>                      #给新节点指定主节点
  del-node       host:port node_id                              #删除给定的一个节点，成功后关闭该节点服务
  call           host:port command arg arg .. arg               #在集群的所有节点执行相关命令
  set-timeout    host:port milliseconds                         #设置cluster-node-timeout
  import         host:port                                      #将外部redis数据导入集群
                 --cluster-from <arg>                           #将指定实例的数据导入到集群
                 --cluster-copy                                 #migrate时指定copy
                 --cluster-replace                              #migrate时指定replace
  help           

For check, fix, reshard, del-node, set-timeout you can specify the host and port of any working node in the cluster.

```

> 注意：Redis Cluster最低要求是3个主节点

### 创建集群主从节点

```
redis-cli --cluster create 172.31.0.11:6379  172.31.0.12:6379  172.31.0.13:6379 172.31.0.14:6379 172.31.0.15:6379 172.31.0.16:6379 --cluster-replicas 1

>>> Performing hash slots allocation on 6 nodes...
Master[0] -> Slots 0 - 5460
Master[1] -> Slots 5461 - 10922
Master[2] -> Slots 10923 - 16383
Adding replica 172.31.0.15:6379 to 172.31.0.11:6379
Adding replica 172.31.0.16:6379 to 172.31.0.12:6379
Adding replica 172.31.0.14:6379 to 172.31.0.13:6379
M: 50fa88c4a01f968df6ab7e8bd02e1bb51c85f13f 172.31.0.11:6379
   slots:[0-5460] (5461 slots) master
M: 04a2118b3f7b7521a55cf77171f1c50fe1a80f4d 172.31.0.12:6379
   slots:[5461-10922] (5462 slots) master
M: b83a282329830e2ea686889cb8aa9eafa3441b8f 172.31.0.13:6379
   slots:[10923-16383] (5461 slots) master
S: 9b0a2284c341efa7055dd2046aec2e1c43ee6f9b 172.31.0.14:6379
   replicates b83a282329830e2ea686889cb8aa9eafa3441b8f
S: 09aca472595a229e7ceda2792aed98f88d757d45 172.31.0.15:6379
   replicates 50fa88c4a01f968df6ab7e8bd02e1bb51c85f13f
S: 2ce485e6a5bc5a3f300347c123ce911e605bf164 172.31.0.16:6379
   replicates 04a2118b3f7b7521a55cf77171f1c50fe1a80f4d
Can I set the above configuration? (type 'yes' to accept): 
```

> --cluster create : 表示创建集群  
> --cluster-replicas 0 : 表示只创建n个主节点，不创建从节点  
> --cluster-replicas 1 : 表示为集群中的每个主节点创建一个从节点（例：master[172.31.0.11:6379] -> slave[172.31.0.14:6379]）  

![](http://img.github.mailjob.net/jefferyjob.github.io/20210208174303.png)


### 搭建问题

##### 创建集群主从节点报错

> [ERR] Node 172.31.0.11:6379 is not configured as a cluster node