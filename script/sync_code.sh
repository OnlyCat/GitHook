#!/bin/bash

# 检查目录是否存在
isDirExist() {
    if  [ -d "$1" ];then
      echo  1
    else
      echo  0
    fi
}

# 获取代码信息
gitPull() {
    git pull > /dev/null 2>&1
    if [ 0 -eq $# ];then
        echo 1
    else
        echo 0
    fi
}

dir_path=$1
is_dir=$(isDirExist $dir_path)
if [ 1 -eq $is_dir ]; then
    cd $dir_path
    echo $(gitPull)
else
    echo 0
fi