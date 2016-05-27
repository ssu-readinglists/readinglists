This file is the README.txt for /filter/refshares

Dependencies
------------

This filter relies on the TELSTAR code, originally developed by the Open University, being installed in the Moodle installation.
More details at http://code.google.com/p/telstar

Function
--------

The filter checks for the existence of URLs for RefShare RSS feeds in the page, and replaces them with a 'styled' reference list
The styled list includes links to online versions of the references or OpenURLs as appropriate.

The style of the displayed reference list can be set by adding '#' followed by the (short) name of the RefWorks style
The names that can be used are the same as those set in the $referencestyles array in /local/references/apibib_lib.php
The 'short name' is keyed as 'string' in this array.

For example:
http://www.refworks.com/refshare/?site=039761155528000000/RWWEB106495192/Learning%20and%20Memory&rss#APA-SSU
http://www.refworks.com/refshare/?site=039761155528000000/RWWEB106495192/Learning%20and%20Memory&rss#Harvard-BS-SSU

If the list cannot be displayed for any reason, an error message is displayed (this can be modified in the accompanying lang file)
Below the error message a link to the RefShare URL will be displayed for the user to click if necessary.

Caching
-------

To improve performance, formatted versions of the refshare references are stored in the cache_filters table as follows:

id = incremental id
filter = refshares
version = 1
md5key = md5 hash of the RefShare RSS URL concatenated with the Style to be applied (using string from $referencestyles as above)
rawtext = html snippet of formatted references
timemodified = UNIX Epoch timestamp of when the cache was last updated

The Filter also has an option that can be set (via Manage Filters in Moodle admin) for how often the cache should be refreshed. Options are:
1 min
15 minutes
1 hour
1 day
1 week
52 weeks

If this amount of time has passed since the cached version was last updated, when the cache is requested it will attempt to update it.
If the cache cannot be updated at that time (e.g. the RefWorks API does not respond, or the RSS feed cannot be retrieved) the existing 
version will be served instead and an error logged.

N.B. SEE NOTE BELOW ON INTERACTION BETWEEN CACHE EXPIRES INTERVAL AND SCHEDULED TASK FREQUENCY

If this filter is used in conjunction with the 'ReadingList' Resource type, a user with appropriate permissions can force a refresh of cached versions.
This happens automatically when a Readinglist resource is saved, and when viewing the reading list, a user with permissions can trigger a refresh.
In both cases this refreshes the cache for all RefShares included in the page.

Scheduled tasks
---------------

A scheduled task can be run (via Moodle cron) to update the cached versions of reading lists.
The frequency with which the job runs can be set via the Administration->Site Administration->Server->Scheduled Tasks options. The default is to run this once a day at 1am.

Never running this scheduled task increases the chances of slow loading lists in the UI as it increases the chances of a user requesting a list for which the cache has expired.

The refshares_cron function does the following:
1) retrieves all cached filters
2) checks which have expired and for these...
3) extracts the RefShare RSS URL and the style from the rawtext string (this is stored in a span id - see "Styling" below)
4) creates a refreshed cached version

Because this task can run for some time, there is a setting in the Filter settings called 'timeout' which allows you to specify a limit to how long this job will run before stopping. If it hasn't refreshed all cached lists in this time it will pick up the remaining lists on the next run.

N.B. THE FREQUENCY WITH WHICH THE SCHEDULED JOB RUNS WILL DICTATE THE MAXIMUM POSSIBLE FREQUENCY FOR UPDATING CACHED VERSIONS OF REFSHARES
IF THE FILTER OPTION IS SET TO EXPIRE THE CACHE AFTER 1 MINUTE BUT THE SCHEDULED JOB RUNS EVERY 5 MINUTES THERE WILL BE A FOUR MINUTE WINDOW DURING WHICH CACHES ARE OUT OF DATE
THIS MEANS THAT IF A USER REQUESTS THE PAGE DURING THESE FOUR MINUTES THE CACHE WOULD BE REFRESHED 'ON THE FLY' WHICH WILL RESULT IN SLOW
RESPONSE TIMES FOR THE USER.
THEREFORE IT IS BETTER FOR THE CACHE EXPIRY TIME IN THE FILTER OPTION TO BE LARGER THAN THE INTERVAL BETWEEN SCHEDULED JOB RUNS

Styling
-------

To enable easy styling, each set of references is wrapped in a <span> tag with a class of 'refshare'.
This <span> has a title of the RefShare RSS URL concatenated with '#' and the style string

Within a set of references, each reference is wrapped in a <span> tag with a class of 'reference'
Any notes with a reference are wrapped in a separate (i.e. not nested withing <span class="reference") <span> with class of 'reference_note'

Link Behaviour
--------------

It is possible to set (via Manage Filters in Moodle admin) whether links from references open in the current or a new window/tab.
N.B. CHANGES TO THIS OPTION WILL ONLY APPLY AS CACHED VERSIONS OF REFERENCES ARE REFRESHED

Contextual Linking
------------------

In many cases OpenURL links are created with the reference. As the filters are not specific to a course/module context in Moodle 1.9 these OpenURLs do not include the course/module ID
