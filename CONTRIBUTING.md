
## State of development and how to contribute

**June 2026**: Emoncms is currently going through a process of refactoring. Over the years we have developed many different ways of doing the same thing in Emoncms:

- On the feature side there is input processing, virtual feeds for post processing, the post processing module, multiple time series storage engines, many similar but slightly different app dashboards, the dashboard module and associated visualisations. 

- On the implementation side on the front end at least we are using mixture of Vue.js, jQuery, bootstrap 2 + bootstrap 4 utility classes + lots of custom CSS.

This expansion in different directions in terms of both features and implementation has made ongoing maintenance and development more difficult. 

The project is at a stage where the focus needs to be more one of narrowing the scope, focusing on the core use case of Emoncms and consolidating and evolving the implementation to achieve a better level of consistency.

Recent changes include:

- Input, feed and device list now all use the same css grid + vue implementation. Removing different implementations for the same ui experience.

- Archiving visualisations and apps that are similar to each other or no longer actively maintained. Consolidation of the MyElectric, MySolar and MySolarPVBattery apps into a single MyElectricFlow app.

- Making use of Vue.js more consistently and upgrading to vue.js v3.

- Upgrading to the under more active development flot 5.1 charting library.

The next step is a more substantial refactoring of Emoncms styling, moving from bootstrap 2 to a combination of custom CSS and standardised utility classes, this will also remove a lot of dead CSS and ui related javascript in the process.


**If helping with this process interests you, please get in touch. Some of the challenges include:**

- How to remove the vis module from core and integrate it directly in the dashboard module. This would make more sense from a dependency perspective as these visualisations are only used by the dashboard module. 

- How to deprecate vis multigraph in favour of using the graph module without breaking existing use of multigraphs in dashboards. 

- Can we replace the AntiXSS dependency in the dashboard module with a more strict and secure white list approach. How do we ensure that dashboards that have lots of custom html properties and css migrate correctly without breaking.

- Making it easier for users to migrate feeds stored as PHPTimeSeries and MysqlTimeSeries over to PHPFina (fixed interval).

- Carefull staged refinement of Emoncms css, there is a lot of cusom css, a fair bit of it is duplicated, we need to carefully extract common css and create an emoncms specific css framework.

---

Please feel free to get in touch:

hello@openenergymonitor.zendesk.com (Trystan, Glyn and Gwil)
or: trystanlea@openenergymonitor.org