<?php
defined('EMONCMS_EXEC') or die('Restricted access');

/*
  Structured description of the schedule module HTTP API.

  Schema per endpoint (consumed by Lib/api_explorer_view.php):
  - description: short human name
  - path:        URL path relative to the emoncms base
  - parameters:  name => array(default, type, options, description)
                 type: "feed" (feed selector), "bool", "select" or text input
  - mode:        "read" or "write" (which apikey is required)
  - group:       section heading used to group endpoints in the docs
  - notes:       extra documentation: aliases, formats, gotchas

  Every endpoint here acts on the schedules of the account the apikey belongs
  to, a schedule owned by another user responds as if it does not exist.
*/

function schedule_api_obj() {

  $expression_notes = tr("Expression format: comma separated rules of date | weekday | time ranges, e.g. '00:00-07:00', 'Mon-Fri|09:00-17:00', 'Summer|Mon-Fri|08:00-09:00' or '12/01-12/31|Sat,Sun|09:00-12:00'. Summer and Winter match daylight saving time. Times are evaluated in the schedule's own timezone.");

  return array(
    // ------------------------------------------------------------------
    // Reading schedules
    // ------------------------------------------------------------------
    array(
      "description" => tr("List schedules"),
      "path" => "schedule/list.json",
      "parameters" => array(),
      "mode" => "read",
      "group" => tr("Reading schedules"),
      "notes" => tr("All schedules on the account: id, userid, name, expression and timezone.")
    ),
    array(
      "description" => tr("Get schedule"),
      "path" => "schedule/get.json",
      "parameters" => array(
        "id" => array( "default" => 1, "description" => tr("Schedule id, see schedule/list.json") )
      ),
      "mode" => "read",
      "group" => tr("Reading schedules")
    ),
    array(
      "description" => tr("Get schedule expression"),
      "path" => "schedule/expression.json",
      "parameters" => array(
        "id" => array( "default" => 1 )
      ),
      "mode" => "read",
      "group" => tr("Reading schedules"),
      "notes" => tr("Just the expression and its timezone.")." ".$expression_notes
    ),
    array(
      "description" => tr("Test schedule expression"),
      "path" => "schedule/test.json",
      "parameters" => array(
        "id" => array( "default" => 1 )
      ),
      "mode" => "read",
      "group" => tr("Reading schedules"),
      "notes" => tr("Evaluates the schedule against the current time and responds with the match result and a description of how the expression was parsed, for debugging an expression.")
    ),

    // ------------------------------------------------------------------
    // Editing schedules
    // ------------------------------------------------------------------
    array(
      "description" => tr("Create schedule"),
      "path" => "schedule/create.json",
      "parameters" => array(),
      "mode" => "write",
      "group" => tr("Editing schedules"),
      "notes" => tr("Creates an empty schedule named 'New Schedule' in the account timezone and responds with its id. Set the name and expression with schedule/set.json.")
    ),
    array(
      "description" => tr("Update schedule"),
      "path" => "schedule/set.json",
      "parameters" => array(
        "id" => array( "default" => 1 ),
        "fields" => array( "default" => '{"expression":"Mon-Fri|00:00-23:59"}', "description" => tr("JSON object, name and expression can be set") )
      ),
      "mode" => "write",
      "group" => tr("Editing schedules"),
      "notes" => $expression_notes." ".tr("Names accept letters, numbers, spaces, underscore, hyphen and colon only.")
    ),
    array(
      "description" => tr("Delete schedule"),
      "path" => "schedule/delete.json",
      "parameters" => array(
        "id" => array( "default" => 0 )
      ),
      "mode" => "write",
      "group" => tr("Editing schedules"),
      "notes" => tr("Deletes the schedule. Any input or feed process that references it stops matching, check your process lists first.")
    )
  );
}
