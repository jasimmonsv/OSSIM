for i in $( ls *.xml); do                                                                                                   
    sed -e 's/<var.*//g' $i > clean/$i
done

