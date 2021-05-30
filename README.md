# mySync for myCloud OS5
## Purpose
This is an addon for WD MyCloud NAS systems running under OS5. Addon utilizes [rclone](https://rclone.org/) which is "a command line program to sync files and directories to and from different cloud storage providers."

You might use mySync to periodically sync selected directories or backup whole NAS to other cloud storage of your choice.

## Disclaimers
1) Use your own judgement to decide whether rclone is secure enough for your needs. You will have to authorize this tool for access to your other cloud account.
2) Get familiar with rclone documentation and make sure to test your rclone configuration in controled environment before deploying to NAS. Wrong configuration might send data to unintended location, overwrite or delete data. 
3) mySync has been created for my personal needs. I have focused on the core function. Once setup it works reliably. Since deployment and setup is a one time event it is not as convenient as you might want.

## Prerequisits
1) WD MyCloud **OS5** SDK - you will need mksapkg tool to build a package. To get OS5 version you need to ask on the [WD Community site](https://community.wd.com/c/os5). [Official site](https://developer.westerndigital.com/develop/wd/sdk.html) contains still the previous OS3 version.
2) rclone - [download](https://github.com/rclone/rclone/releases) or compile binary for your NAS and place it under mySync/bin/ directory.

## How to configure
There are two configuration files you will have to customize:
### mySync/etc/rclone.conf
Configure your connection using:
```
rclone config
```

Locate generated configuration file:
```
rclone config file
```

Copy it to mySync/etc/.

### mySync/etc/rclone_job_def.conf
Define one synchronization operation per line by providing following information
```
source_path|remote:target_path|options
```
where:
* *source_path* is a path on your WD device, e.g. /mnt/HD/HD_a2/Public
* *remote* is a name of your rclone defined remote site
* *target_path* is a path on the remote site
* *options* are optional rclone options you might want to define, e.g. --max-duration duration

## How to build
Before building make sure your configuration files are in the mySync/etc/ and rclone is under mySync/bin/.

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
In the MyCloud Administration Console select Apps menu and use "Install an app manually". Select the package you have just built.

## Troubleshooting
Logs are kept by default here: /mnt/HD/HD_a2/.systemfile/mySync/log

## Appendix: Functions
- [x] Remote backup with plenty of connectivity options
- [x] Multiple sync jobs
- [x] Automatically restarts after NAS restart
- [ ] Integration with notification services of MyCloud
- [ ] Online log browsing in MyCloud Administration Console
- [ ] Online configuration in MyCloud Administration Console
