# 使用shell脚本生成自签名证书

在不同项目环境配置内部https服务的时候需要使用不同的证书，为了简化生成证书时手动执行命令的繁琐，写了一个shell脚本来生成证书文件。

## 脚本说明

### shell脚本的使用说明如下：

脚本中使用openssl命令生成证书，执行前需要保证openssl命令可用。
脚本在centos 7和ubuntu 16.04中已经验证通过；在windows中的git bash里无法正确执行，不要在windows上的git bash里面执行脚本。
脚本命令格式如下：

```
sh gen-cert.sh -a 算法 -d 域名 -n 证书文件名
```

```
sh gen-cert.sh -a ecc -d test.com,a.com,*.a.com -n test
```


脚本中的参数说明：

- -a 生成的证书中使用的算法，有rsa和ecc两种选项，rsa会生成2048位的key，ecc生成prime256v1的key；
- -d 证书中的域名，可以支持写多个域名，多个域名使用逗号分隔。第一个域名会作为CN（common name），这个参数里面所有的域名会写入证书的SAN（通过这可以一个证书支持多个不同域名）。
- -n 生成的服务器证书文件名。脚本生成的证书文件都放在certs目录下，如果目录下已经存在同名的证书文件则会跳过。第二次执行脚本时，如果-n参数指定为与第一次不同的名称，则会使用第一次生成的CA证书签发新的服务器证书。
- -h 查看脚本帮助。




### shell脚本的内容如下：

```
#!/bin/bash

usage()  
{  
    echo "Usage: $0 [-a [rsa|ecc]] [-d <domain>] [-n <name>] [-h]"  
    echo "  Options:"
    echo "    -a  algorithm.[rsa|ecc]"
    echo "    -d  domain.example: xxx.com,abc.org,*.abc.org"
    echo "    -n  server key name"   
    echo "    -h  help"  
    exit 1  
} 

srv_key_name="server"

while getopts "a:d:n:h" arg #选项后面的冒号表示该选项需要参数
do
    case $arg in
        a)
            alg=$OPTARG #算法
            ;;
        d)
            all_domain=$OPTARG #域名,逗号分隔
            ;;
        n)
            srv_key_name=$OPTARG #服务器证书名称
            ;;
        h)
            usage
            exit 0
            ;;
        ?)  #当有不认识的选项的时候arg为?
            usage
            exit 1
            ;;
    esac
done

domain="domain.com"
san="DNS:*.${domain},DNS:${domain}"
if [ -n "${all_domain}" ]; then
    #分割域名
    OLD_IFS="$IFS"  
    IFS="," 
    domain_array=($all_domain)
    IFS="$OLD_IFS"  

    domain_len=${#domain_array[@]} 
      
    domain=${domain_array[0]}
    san=""
    for ((i=0;i<domain_len;i++))
   {
    if [ $i = 0 ];then
        san="DNS:${domain_array[i]}"
    else
        san="${san},DNS:${domain_array[i]}"
    fi
   }
fi

ca_subj="/C=CN/ST=Hubei/L=Wuhan/O=MY/CN=MY CA"
server_subj="/C=CN/ST=Hubei/L=Wuhan/O=MY/CN=${domain}"
#其中C是Country，ST是state，L是local，O是Organization，OU是Organization Unit，CN是common name
days=14610 # 有效期40年
echo "san:${san}"

sdir="certs"
ca_key_file="${sdir}/ca.key"
ca_crt_file="${sdir}/ca.crt"
srv_key_file="${sdir}/${srv_key_name}.key"
srv_csr_file="${sdir}/${srv_key_name}.csr"
srv_crt_file="${sdir}/${srv_key_name}.crt"
srv_p12_file="${sdir}/${srv_key_name}.p12"
srv_fullchain_file="${sdir}/${srv_key_name}-fullchain.crt"
cfg_san_file="${sdir}/san.cnf"


#algorithm config
if [[ ${alg} = "rsa" ]] ; then
    rsa_len=2048
elif [[ ${alg} = "ecc" ]] ; then
    ecc_name=prime256v1
else 
    usage 
    exit 1
fi     #ifend

echo "algorithm:${alg}"

mkdir -p ${sdir}

if [ ! -f "${ca_key_file}" ]; then
    echo  "------------- gen ca key-----------------------"
    if [[ ${alg} = "rsa" ]] ; then
        openssl genrsa -out ${ca_key_file} ${rsa_len}
    elif [[ ${alg} = "ecc" ]] ; then
        openssl ecparam -out ${ca_key_file} -name ${ecc_name} -genkey
    fi     #ifend

    openssl req -new -x509 -days ${days} -key ${ca_key_file} -out ${ca_crt_file} -subj "${ca_subj}"
fi


if [ ! -f "${srv_key_file}" ]; then
    echo  "------------- gen server key-----------------------"
    if [[ ${alg} = "rsa" ]] ; then
        openssl genrsa -out ${srv_key_file} ${rsa_len}
    elif [[ ${alg} = "ecc" ]] ; then
        openssl ecparam -genkey -name ${ecc_name} -out ${srv_key_file}
    fi     #ifend

    openssl req -new  -sha256 -key ${srv_key_file} -out ${srv_csr_file} -subj "${server_subj}"

    printf "[ SAN ]\nauthorityKeyIdentifier=keyid,issuer\nbasicConstraints=CA:FALSE\nkeyUsage = digitalSignature, nonRepudiation, keyEncipherment, dataEncipherment\nsubjectAltName=${san}" > ${cfg_san_file}
    openssl x509 -req  -days ${days} -sha256 -CA ${ca_crt_file} -CAkey ${ca_key_file} -CAcreateserial -in ${srv_csr_file}  -out ${srv_crt_file} -extfile ${cfg_san_file} -extensions SAN
    cat ${srv_crt_file} ${ca_crt_file} > ${srv_fullchain_file}

    openssl pkcs12 -export -inkey ${srv_key_file} -in ${srv_crt_file} -CAfile ${ca_crt_file} -chain -out ${srv_p12_file}
fi
```

脚本执行示例

执行命令下面命令生成证书，生成pkcs12格式证书过程中会提示输入证书密码，请保持两次输入一致。虽然输入密码时可以直接回车设为空，由于某些使用证书的场景必须要密码，所以最好设置一个密码。生成的文件中ca.crt与ca.key为CA证书的公钥与私钥；test.crt与test.key为服务器证书的公钥与私钥；test.p12为pkcs12格式的文件，包含了公私钥。



```
sh gen-cert.sh -a ecc -d test.com,a.com,*.a.com -n test
```


- 生成证书
- 生成的服务器证书中“颁发给”为 test.com ，即 -d 参数中指定的第一个域名。
- 证书信息-常规
- 签名算法采用的ECC算法。
- 证书信息-详细信息-算法
- 使用者可选名称包含了-d参数中指定的所有域名。