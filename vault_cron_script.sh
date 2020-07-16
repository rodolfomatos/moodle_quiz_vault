#!/bin/bash
# 
#      "Moodle Quizzes Vault CRON script"
#
# rodolfo@uporto.pt
#

# Configuration --------------------------------------------------------

source $(dirname $0)/conf/config_bash.cfg

myself="${0##*/}"
mydate="$(date +%Y%m%d%H%M%S)"

LOCKFILE=$TMPDIR/$myself.lck
#LOGFILE=$LOGS/$myself\_$mydate.log
LOGFILE=$LOGS/$myself.log

# Functions ------------------------------------------------------------

source $LIBDIR/lib.sh

# MAIN  ----------------------------------------------------------------

# 1. Criar os directorios auxiliares se necessario
if [ ! -d "$LOGS" ]; then
	mkdir -p {$LOGS, $TMPDIR}
fi

# 2. Verificar se o ficheiro de lock esta activo e se ja prescreveu
if [ -f $LOCKFILE ]
then
	# devemos remover o lockfile a bruta?
       removeornot $LOCKFILE
       RETVAL=$?
       
       if [ $RETVAL -eq 1 ]
       then
           let PIDFILE="$(cat $LOCKFILE)"
           echo "$LOCKFILE is older than $TIME (secs). Removing..."
           echo "... killing parent process"; kill -9 $(echo $PIDFILE)
           echo "... removing lock"; rm -f $LOCKFILE
           # LOG all activities
           echo "$(date): Lockfile is old! ($TIME secs) - Killed process ($PIDFILE)" >> $LOGFILE
       else
           echo "$LOCKFILE is NOT older than 1 hour ( $TIME (secs) ). NOT Removing..."
       fi 
fi

#-----------------------------------------------------------------------

# 3. if there is no lockfile we can start the backup process...
if [ ! -f $LOCKFILE ] 
then
    echo $$ > $LOCKFILE
    echo "$(date): Start Backup process" >> $LOGFILE

#=======================================================================

	$BASE/vault.php >> $LOGFILE 2>&1

#=======================================================================
    rm -f $LOCKFILE 
    echo "$(date): End Backup process (Lockfile removed)" >> $LOGFILE
fi
#-----------------------------------------------------------------------
