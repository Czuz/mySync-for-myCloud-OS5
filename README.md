# mySync for myCloud OS5
## Purpose
This is an addon for WD MyCloud NAS systems running under OS5. Addon utilizes [rclone](https://rclone.org/) which is "a command line program to sync files and directories to and from different cloud storage providers."

You might use mySync to periodically sync selected directories or backup whole NAS to other cloud storage of your choice.

## Disclaimers
1) Use your own judgement to decide whether rclone is secure enough for your needs. You will have to authorize this tool for access to your other cloud account.
2) Get familiar with rclone documentation and make sure to test your rclone configuration in controled environment before deploying to NAS. Wrong configuration might send data to unintended location, overwrite or delete data. 
3) mySync has been created for my personal needs. As long as it fulfills my expectations I don't plan heavy involvement in further development.

## How to build package from sources
### Prerequisits
1) WD MyCloud **OS5** SDK - you will need mksapkg tool to build a package. You can:
   - Check if the [official site](https://developer.westerndigital.com/develop/wd/sdk.html) has been updated to include mksapkg for OS5.
   - Check on the [official WD community site](https://community.wd.com/t/whare-are-the-os5-sdk-tools/266486/3).
   - Download from some unofficial source at your own risk (expect md5sum: 15ec19d9bf8c7c46f52e9017fd426c3e)
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
There are two configurations you will have to privide:
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

Remember to escape spaces and special characters in paths with "\", e.g.: `/mnt/HD/HD_a2/Public/Tim\ O\'Raily`

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

## Troubleshooting
In the MyCloud Administration Console select `Apps menu -> mySync -> Configure -> Logs`.

By default logs are kept here: /mnt/HD/HD_a2/.systemfile/mySync/log

## Changes
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
- [ ] Integration with notification services of MyCloud
- [ ] Log retention
