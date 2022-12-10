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
dirList=$(ls -l | grep ^d | awk '{print $NF}')
mkdir -p pack
# 遍历所有目录
for dir in $dirList; do
    # 将目录名按大小写进行转换并以-分割
    filename=$(echo $dir | sed 's/\([A-Z]\)/-\1/g' | sed 's/^-//' | tr '[:upper:]' '[:lower:]')
    # 打包
    cd $dir
    zip -qr ../pack/$filename.zip * -x *.git* -x *.idea* -x *.DS_Store*
    cd ..
done
