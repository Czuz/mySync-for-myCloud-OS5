# mySync for myCloud OS5
## Purpose
This is an add-on for WD MyCloud NAS systems running OS5. The add-on utilizes [rclone](https://rclone.org/), which is "a command line program to sync files and directories to and from different cloud storage providers."

You can use mySync to periodically sync selected directories or backup the whole NAS to other cloud storage of your choice.

## Disclaimers
1) Use your own judgement to decide whether rclone is secure enough for your needs. You will have to authorize this tool for access to your other cloud account.
2) Get familiar with rclone documentation and make sure to test your rclone configuration in a controlled environment before deploying to the NAS. Wrong configuration might send data to an unintended location, overwrite, or delete data. 
3) mySync has been created for my personal needs. As long as it fulfills my expectations I don't plan heavy involvement in further development.

## How to build package from sources
### Prerequisites
1) WD MyCloud **OS5** SDK - you will need mksapkg tool to build a package. You can:
   - Check if the [official site](https://developer.westerndigital.com/develop/wd/sdk.html) has been updated to include mksapkg for OS5.
   - Check on the [official WD community site](https://community.wd.com/t/whare-are-the-os5-sdk-tools/266486/3).
   - Download from some unofficial source at your own risk (expect md5sum: 849d3ce1153d674684f9f7243e5a8679)
3) rclone - [download](https://github.com/rclone/rclone/releases) or compile binary for your NAS and place it under mySync/bin/ directory.

### How to build
Before building make sure rclone is under mySync/bin/.

In the mySync directory execute:
```
mksapkg -E -s -m <module_name>
```

where *module_name* is one of:
* WDMyCloud
* WDMyCloudEx4100
* WDMyCloudMirror
* MyCloudEX2Ultra
* MyCloudPR4100
* MyCloudPR2100
* WDCloud

## How to install
In the MyCloud Administration Console select Apps menu and use "Install an app manually". Select the package you have built by yourself or downloaded from this site.

## How to configure
There are two configurations you will have to provide:
### Remote Connections
Configure your connection using rclone locally on your desktop:
```
rclone config
```

Locate generated rclone.conf configuration file:
```
rclone config file
```

In the MyCloud Administration Console select `Apps menu -> mySync -> Configure -> Remote Connections`.

Select rclone.conf from your drive and Save it.

### Backup Flows
In the MyCloud Administration Console select `Apps menu -> mySync -> Configure -> Backup Flows`.

Define one synchronization operation per line by providing following information
```
source_path|remote:target_path|options
```
where:
* *source_path* is a path on your WD device, e.g. /mnt/HD/HD_a2/Public
* *remote* is a name of your rclone defined remote site
* *target_path* is a path on the remote site
* *options* are optional rclone options you might want to define, e.g. --max-duration duration

Remember to escape spaces and special characters in paths with "\", e.g.: `/mnt/HD/HD_a2/Public/Tim\ O\'Reilly`

By default following options are used:
```
--create-empty-src-dirs
--log-level INFO
--delete-after
--copy-links
--retries 10
--retries-sleep 60s
--stats 0
--stats-one-line
--log-file
```

Save the configuration and restart the service.

## How to setup notifications
In the MyCloud Administration Console select `Settings -> Notifications`.

Configure according to your preferences. mySync failures will be reported as the following *Warning*:
> Remote Backup Error
>
> An error occurred for the remote backup job named mySync. Please check the backup job detail.
>
> Code: 1400

## How to setup log retention
In the MyCloud Administration Console, select `Apps menu -> mySync -> Configure -> Settings`.

Enter the desired number of days to keep logs and click Save. Entering "-1" means logs are kept forever.

## Troubleshooting
In the MyCloud Administration Console, select `Apps menu -> mySync -> Configure -> Logs`.

By default, logs are kept here: /mnt/HD/HD_a2/.systemfile/mySync/log

## Changes
\[2025-11-30\] - v1.2.0
- Added log rotation
- Added configurable logs retention

\[2022-11-20\] - v1.1.0
- Integration with notification services of MyCloud

\[2021-11-01\] - v1.0.0
- mySync job to perform regular backups
- Web GUI for mySync administration:
   - Upload rclone.conf
   - Define folders for backup
   - Browse and search log files

\[2021-10-22\] - v0.9.2
- First version of Online log browsing in MyCloud Administration Console
- Several fixes to file permissions and apkg.rc

## Appendix: Functions
- [x] Remote backup with plenty of connectivity options
- [x] Multiple sync jobs
- [x] Automatically restarts after NAS restart
- [x] Online log browsing in MyCloud Administration Console
- [x] Online configuration in MyCloud Administration Console
- [x] Integration with notification services of MyCloud
- [x] Log retention
