#!/bin/bash
shopt -s expand_aliases
PATH=/bin:/sbin:/usr/bin:/usr/sbin:/usr/local/bin:/usr/local/sbin:~/bin
export PATH

year=$(date +%Y)
LOGO="+----------------------------------------------------\n| 耗子Linux面板安装脚本\n+----------------------------------------------------\n| Copyright © 2022-"$year" 耗子 All rights reserved.\n+----------------------------------------------------"
HR="+----------------------------------------------------"

# 插件打包脚本
# 作者: 耗子

rm -rf pack
mkdir -p pack
# 遍历所有目录
for dir in `ls -l | grep ^d | awk '{print $NF}'`
do
    # 将目录名转为小写，作为打包文件名
    filename=`echo $dir | tr '[A-Z]' '[a-z]'`
    # 打包
    cd $dir
    zip -r ../pack/$filename.zip * -x *.git* -x *.idea* -x *.DS_Store*
    cd ..
done
