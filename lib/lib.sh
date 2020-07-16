function removeornot () {
 
       CURRDATE=$(date +%s)
       FILEDATE=$(date -r $1 +%s)
 
       #3 hours is more than enough!
       let TIME=60*60*3
       let DIFF=$CURRDATE-$FILEDATE
 
       if [ "$DIFF" -gt "$TIME" ]
       then
               return 1
       else
               return 0
       fi
}
