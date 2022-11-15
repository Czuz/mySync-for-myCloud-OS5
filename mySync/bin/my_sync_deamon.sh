#!/bin/bash
############################################################################
# Program      : my_sync_deamon.sh                                         #
# Part of      : mySync (https://github.com/Czuz/mySync-for-myCloud-OS5)   #
# Type         : SHELL                                                     #
#                                                                          #
# Purpose      : Execution of rclone synchronization jobs                  #
#              :                                                           #
# Originator   : Czuz                          (https://github.com/Czuz/)  #
# Author       : Czuz                          (https://github.com/Czuz/)  #
# Initial Date : 24-Oct-2020                                               #
############################################################################
# Parameters                                                               #
# No   Description                        Example                          #
# ---- ---------------------------------- -------------------------------- #
#    1 [Optional] Sleep time in seconds   60                               #
#                                                                          #
############################################################################
# Exit Codes                                                               #
# Code Description                                                         #
# ---- ------------------------------------------------------------------- #
#    0 Ended OK                                                            #
#    1 Unable to create main log file, permission general issue            #
#    2 Environment variable CONF_DIR is not set                            #
#    3 Wrong number of parameters                                          #
#    4 Configuration file does not exists or is not readable               #
#    5 Errors found in configuration file                                  #
#    6 rclone execution failed                                             #
#                                                                          #
############################################################################
# History                                                                  #
# Date        Programmer          Description                              #
# ----------- ------------------- ---------------------------------------- #
# 24-Oct-2020 Czuz                Initial Version                          #
############################################################################
# STEP001 -- Initialize Standard Variables                                 #
############################################################################
. my_sync_deamon.conf
if [ ! -e /dev/fd ]; then
  # Fix for "process substitution" bug described here:
  # http://buildroot-busybox.2317881.n4.nabble.com/bash-dev-fd-62-No-such-file-or-directory-td189009.html
  ln -s /proc/self/fd /dev/fd
fi

INIT_EXE=${0}
INIT_PID=${$}
INIT_PGM=`basename ${INIT_EXE}`
INIT_DIR=`dirname ${INIT_EXE}`
INIT_VER="1.0"

LOG_DATE=`date +%Y%m%d%H%M%S`
LOG_FILE_NAME=${INIT_PGM}.${LOG_DATE}.${INIT_PID}

LOG_FILE=${LOG_DIR}/${LOG_FILE_NAME}.log
LOG_RCLL=${LOG_DIR}/${LOG_FILE_NAME}.rclone.log
CONF_FILE=${CONF_DIR}/rclone_job_def.conf

EXIT_CODE=0
HUP=0
trap "HUP=1; echo \"`date +%Y-%m-%d\ %H:%M:%S` SIGHUP  : Termination signal\" | tee -a ${LOG_FILE}; pkill -P $$" SIGINT SIGHUP

touch ${LOG_FILE} 
RC=${?}

echo "`date +%Y-%m-%d\ %H:%M:%S` START   : ${INIT_EXE}"                                                           | tee -a ${LOG_FILE}
echo "`date +%Y-%m-%d\ %H:%M:%S` STEP001 : Initialize Standard Variables (BEGIN)"                                 | tee -a ${LOG_FILE}

if [ ${RC} -ne 0 ] ; then
  echo "`date +%Y-%m-%d\ %H:%M:%S` STEP001 : Unable to create ${LOG_FILE}, please check permissions"              | tee -a ${LOG_FILE}
  echo "`date +%Y-%m-%d\ %H:%M:%S` STEP001 : Initialize Standard Variables (FAILED)"                              | tee -a ${LOG_FILE}
  fireAlert -a 1400 -p mySync -f
  exit 1
fi
echo "`date +%Y-%m-%d\ %H:%M:%S` STEP001 : Script ${INIT_EXE} is being executed by `whoami` on `date`"            | tee -a ${LOG_FILE}

echo "`date +%Y-%m-%d\ %H:%M:%S` STEP001 : Main LOG File     : ${LOG_FILE}"                                       | tee -a ${LOG_FILE}
echo "`date +%Y-%m-%d\ %H:%M:%S` STEP001 : rclone Log File   : ${LOG_RCLL}"                                       | tee -a ${LOG_FILE}

if [ ${CONF_DIR:-none} = none ] ; then
  echo "`date +%Y-%m-%d\ %H:%M:%S` STEP001 : Variable CONF_DIR not set - unable to continue"                      | tee -a ${LOG_FILE}
  echo "`date +%Y-%m-%d\ %H:%M:%S` STEP001 : Initialize Standard Variables (FAILED)"                              | tee -a ${LOG_FILE}
  fireAlert -a 1400 -p mySync -f
  exit 2
fi

if [ ${SLEEP_TIME:-none} = none ] || [[ ! ${SLEEP_TIME} =~ ^[0-9]+$ ]] ; then
  echo "`date +%Y-%m-%d\ %H:%M:%S` STEP001 : Variable SLEEP_TIME not set - setting default to 60s"                | tee -a ${LOG_FILE}
  SLEEP_TIME=60
fi

echo "`date +%Y-%m-%d\ %H:%M:%S` STEP001 : Initialize Standard Variables (END-OK)"                                | tee -a ${LOG_FILE}

##########################################################################
# STEP010 -- Checking in-comming parameters                              #
##########################################################################
echo "`date +%Y-%m-%d\ %H:%M:%S` STEP010 : Checking InComming Parameters (BEGIN)"                                 | tee -a ${LOG_FILE}
echo "`date +%Y-%m-%d\ %H:%M:%S` STEP010 : Number of passed parameters : ${#}"                                    | tee -a ${LOG_FILE}
echo "`date +%Y-%m-%d\ %H:%M:%S` STEP010 :                    they are : ${*}"                                    | tee -a ${LOG_FILE}

case ${#} in
  0) IN_SLEEP_TIME=${SLEEP_TIME}
     ;;
  1) IN_SLEEP_TIME=${1}
     ;;
  *) echo "`date +%Y-%m-%d\ %H:%M:%S` STEP010 : Incorrect number of parameters"                                   | tee -a ${LOG_FILE}
     echo "`date +%Y-%m-%d\ %H:%M:%S` STEP010 : Usage : ${INIT_EXE} [SLEEP_TIME]"                                 | tee -a ${LOG_FILE}
     fireAlert -a 1400 -p mySync -f
     exit 3
     ;;
esac

if [ ${IN_SLEEP_TIME:-none} = none ] || [[ ! ${IN_SLEEP_TIME} =~ ^[0-9]+$ ]] ; then
  echo "`date +%Y-%m-%d\ %H:%M:%S` STEP010 : Incorrect Sleep Time (${IN_SLEEP_TIME}) - falling back to default"   | tee -a ${LOG_FILE}
  IN_SLEEP_TIME=${SLEEP_TIME}
fi

echo "`date +%Y-%m-%d\ %H:%M:%S` STEP010 : Sleep Time : ${IN_SLEEP_TIME}"                                         | tee -a ${LOG_FILE}
echo "`date +%Y-%m-%d\ %H:%M:%S` STEP010 : Checking InComming Parameters (END-OK)"                                | tee -a ${LOG_FILE}

##########################################################################
# STEP020 -- Check synchronization configuration                         #
##########################################################################
echo "`date +%Y-%m-%d\ %H:%M:%S` STEP020 : Check synchronization configuration (BEGIN)"                           | tee -a ${LOG_FILE}

if [ ! -r ${CONF_FILE} ] ; then
  echo "`date +%Y-%m-%d\ %H:%M:%S` STEP020 : File ${CONF_FILE} does not exist or is not readable"                 | tee -a ${LOG_FILE}
  echo "`date +%Y-%m-%d\ %H:%M:%S` STEP020 : Check synchronization configuration (FAILED)"                        | tee -a ${LOG_FILE}
  fireAlert -a 1400 -p mySync -f
  exit 4
fi

declare -A sync_jobs
i=0
VALID_JOB_NO=0
INVALID_JOB_NO=0
INVALID_JOB_LIST=()
ALL_JOBS=""

while read LINE; do
IFS="|" read P_SRC_PATH P_TGT_PATH P_OPTIONS <<< "$LINE"
  ((i++))

  sync_jobs[$i,configuration_line]="${LINE}"
  sync_jobs[$i,src_path]=${P_SRC_PATH}
  sync_jobs[$i,tgt_path]=${P_TGT_PATH}
  sync_jobs[$i,options]=${P_OPTIONS}
  sync_jobs[$i,has_error]=0
  sync_jobs[$i,error_message]=""

  if [ ${sync_jobs[$i,has_error]} -eq 0 -a "${P_SRC_PATH:-none}" = none ] ; then
    sync_jobs[$i,has_error]=1
    sync_jobs[$i,error_message]="Source path has not been set in the configuration"
  fi

  if [ ${sync_jobs[$i,has_error]} -eq 0 -a "${P_TGT_PATH:-none}" = none ] ; then
    sync_jobs[$i,has_error]=1
    sync_jobs[$i,error_message]="Target path has not been set in the configuration"
  fi

  # if [ ${sync_jobs[$i,has_error]} -eq 0 ] && [ ! -r "${P_SRC_PATH}" ] ; then
  #   sync_jobs[$i,has_error]=1
  #   sync_jobs[$i,error_message]="Source path does not exist"
  # fi

  if [ ${sync_jobs[$i,has_error]} -eq 1 ] ; then
    (( INVALID_JOB_NO++ ))
    INVALID_JOB_LIST+=( "${sync_jobs[$i,configuration_line]} <= ${sync_jobs[$i,error_message]}" )
  else
    (( VALID_JOB_NO++ ))
  fi
done < <(cat ${CONF_FILE} | grep -v "^#")

PART_1="${VALID_JOB_NO} valid definition"
[[ ${VALID_JOB_NO} -ne 1 ]] && PART_2="s" || PART_2=""
[[ ${INVALID_JOB_NO} -gt 0 ]] && PART_3=", ${INVALID_JOB_NO} invalid definition" || PART_3=""
[[ ${INVALID_JOB_NO} -gt 1 ]] && PART_4="s" || PART_4=""
TOTAL_JOB_NO=${i}
TOTAL_JOB_NO_MSG=$(printf "%d (%s%s%s%s)" ${i} "${PART_1}" "${PART_2}" "${PART_3}" "${PART_4}")

echo "`date +%Y-%m-%d\ %H:%M:%S` STEP020 : # of Jobs         : ${TOTAL_JOB_NO_MSG}"                               | tee -a ${LOG_FILE}

if [ ${INVALID_JOB_NO} -gt 0 ] ; then
  echo "`date +%Y-%m-%d\ %H:%M:%S` STEP020 : Invalid definitions:"                                                | tee -a ${LOG_FILE}
  for j in ${!INVALID_JOB_LIST[@]}; do echo ${INVALID_JOB_LIST[$j]}; done | \
  sed "s/^/`date +%Y-%m-%d\ %H:%M:%S` STEP020 : * /"                                                              | tee -a ${LOG_FILE}
  fireAlert -a 1400 -p mySync -f
fi


if [ ${VALID_JOB_NO} -eq 0 ] ; then
  echo "`date +%Y-%m-%d\ %H:%M:%S` STEP020 : File ${CONF_FILE} does not contain valid definitions"                | tee -a ${LOG_FILE}
  echo "`date +%Y-%m-%d\ %H:%M:%S` STEP020 : Check synchronization configuration (FAILED)"                        | tee -a ${LOG_FILE}
  fireAlert -a 1400 -p mySync -f
  exit 5
fi



echo "`date +%Y-%m-%d\ %H:%M:%S` STEP020 : Check synchronization configuration (END-OK)"                          | tee -a ${LOG_FILE}

##########################################################################
# STEP030 -- Execute synchronization jobs                                #
##########################################################################
echo "`date +%Y-%m-%d\ %H:%M:%S` STEP030 : Execute synchronization jobs (BEGIN)"                                  | tee -a ${LOG_FILE}
while [ ${EXIT_CODE} -eq 0 ] && [ ${HUP} -eq 0 ]
do
  if [ ${HUP} -eq 0 ] ; then
    sleep $IN_SLEEP_TIME &

    for ((i=1;i<=$TOTAL_JOB_NO;i++)); do
      if [ ${sync_jobs[$i,has_error]} -eq 0 ]; then
        rm -f ${LOG_RCLL} 2>/dev/null

        # Troubleshooting:
        #   x509: failed to load system roots... - update certificates (http://rclone.org/faq/) or as a temporary workaround add --no-check-certificate below

        rclone sync "${sync_jobs[$i,src_path]}" "${sync_jobs[$i,tgt_path]}" \
          ${sync_jobs[$i,options]} \
          --create-empty-src-dirs \
          --log-level INFO \
          --delete-after \
          --copy-links \
          --retries 10 \
          --retries-sleep 60s \
          --stats 0 \
          --stats-one-line \
          --log-file "${LOG_RCLL}"
        RC=${?}

        cat ${LOG_RCLL} | sed "s/^\([0-9]\{4\}\).\([0-9]\{2\}\).\([0-9]\{2\} [0-9]\{2\}.[0-9]\{2\}.[0-9]\{2\}\) /\1-\2-\3 STEP030 : [${i}] /" | tee -a ${LOG_FILE}

        if [ ${RC} -ne 0 ] ; then
          echo "`date +%Y-%m-%d\ %H:%M:%S` STEP030 : Execute synchronization jobs (FAILED)"                       | tee -a ${LOG_FILE}
          fireAlert -a 1400 -p mySync -f
          EXIT_CODE=6
          break
        fi
      fi
    done
  fi

  wait
done

if [ ${EXIT_CODE} -eq 0 ] ; then
  echo "`date +%Y-%m-%d\ %H:%M:%S` STEP030 : Execute synchronization jobs (END-OK)"                               | tee -a ${LOG_FILE}
fi

#########################################################################
# STEP999 -- CleanUp and Exit                                           #
#########################################################################
echo "`date +%Y-%m-%d\ %H:%M:%S` STEP999 : CleanUp and Exit (BEGIN)"                                              | tee -a ${LOG_FILE}

rm -f ${LOG_RCLL} 2>/dev/null

echo "`date +%Y-%m-%d\ %H:%M:%S` STEP999 : CleanUp and Exit (END-OK)"                                             | tee -a ${LOG_FILE}
echo "`date +%Y-%m-%d\ %H:%M:%S` STOP    : ${INIT_EXE} completed successfully"                                    | tee -a ${LOG_FILE}

exit ${EXIT_CODE}
