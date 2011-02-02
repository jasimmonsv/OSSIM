for i in $( ls clean/*.xml); do                                                                                                   
    python test.py $i
done

