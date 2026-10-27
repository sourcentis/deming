# Controls

This part allows you to define, modify and plan new controls.

## List of controls <a name="list"></a>

The list of controls allows you to display the list of controls, to filter them by domain or to search for a control based on part of its name.
The "Column visibility", "Copy" and "Export" buttons let you choose the displayed columns, copy the list or export it to Excel.

[![Screenshot](images/m1.png)](images/m1.png)

Clicking on :

* the domain, you arrive at the [chosen domain definition](config.md/#domains)

* the clause, you arrive on the [description of the control](#show)

* the number of measurements, you arrive to the [list of measurements](controls.md/#list) of this control

* the "plan" button takes you to the [control planning](#plan)

The "New" button lets you [create a new control](#create).


## Show control <a name="show"></a>

This screen displays a control.

[![Screenshot](images/m2.png)](images/m2.png)

A control consists of:

* a domain;

* a clause ;

* a name;

* a security objective;

* attributes;

* the measurement data;

* the verification model;

* an indicator (green, orange, red); and

* an action plan to be applied if control of this control fails.

The screen also lists the measurements of this control (scope, planning date, completion date and score), and the
[correspondences](crosswalk.md) of this control with the controls of other frameworks.

When you click:

* “Plan”: you arrive at the [control planning](#plan) screen.

* "Edit": you arrive at [the control edit screen](#edit)

* "Copy": you arrive at the [control creation](#create) screen, pre-filled with the data of this control

* “Delete”: allows you to delete the control and return to [the list of controls](#list)

* "Cancel": returns you to the [list of controls](#list)

When the [AI agent](ai.md) is enabled, the ✦ "AI analysis" icon, at the top right of the screen below the help
button, opens a panel with an AI analysis of the control. See [Use the analysis](ai.md#use).

## Create a control {#create}

This screen is used to add a control to a framework. It is opened by the "New" button of the
[list of controls](#list) or by the "Copy" button of the [control display](#show) screen.

[![Screenshot](images/m4.png)](images/m4.png)

The screen contains the same fields as the [control display](#show): domain, clause, name, security objective,
attributes, measurement data, verification model, indicator and action plan.

When you click:

* "Save", the control is created and you return to the [list of controls](#list).

* "Cancel", you return to the [list of controls](#list).

Controls can also be imported from a spreadsheet or from a framework of the repository through the
[import](config.md#import) screen.


## Edit a control <a name="edit"></a>

This screen allows you to modify a control.

[![Screenshot](images/m3.png)](images/m3.png)


When you click:

* "Save", the changes are saved and you return to the [control display](#show) screen.

* "Cancel", you return to the [control display](#show) screen.


## Schedule a control <a name="plan"></a>

This screen is used to plan a control based on a control.

The screen contains:

* One or more clauses;

* The name of the control;

* The security objective;

* The planning date;

* The scope;

* The frequency of checks; and

* Those responsible for carrying out the controls.

[![Screenshot](images/plan.png)](images/plan.png)

When you click:

* "Plan", a new measurement is created for the given scope by copying all the control data, and is scheduled for
  the specified date. You then return to the [list of controls](#list).

* "Cancel", you return to the [list of controls](#list).
