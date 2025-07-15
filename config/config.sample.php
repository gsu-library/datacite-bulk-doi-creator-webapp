<?php
/**
 * Configuration.
 *
 * @var array  [url, doiPrefix, username, password, maxSubmittedFiles, maxReportedFiles,
 *              maxUploadSize, orcidTokenUrl, orcidApiUrl, orcidClientId, orcidSecret]
 */
const CONFIG = [
   'url' => 'https://api.datacite.org/dois',
   'username' => '',
   'password' => '',
   'maxSubmittedFiles' => 20,
   'maxReportFiles' => 20,
   'maxUploadSize' => 10240,
   'orcidTokenUrl' => 'https://orcid.org/oauth/token',
   'orcidApiUrl' => 'https://pub.orcid.org/',
   'orcidClientId' => '',
   'orcidSecret' => ''
];
