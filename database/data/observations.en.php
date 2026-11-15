<?php

/*
 * Test observations used by the deming:generate-tests command.
 *
 * For each clause of the ISO 27001:2022 framework, an observation
 * consistent with each score: 3 = compliant, 2 = partially compliant,
 * 1 = non-compliant.
 */

return [
    '27001:2022-4.x' => [
        3 => "The organisation's context, interested parties and ISMS scope are documented and reviewed annually. The last review is less than twelve months old and was approved by management.",
        2 => 'The ISMS scope is defined, but the analysis of interested parties has not been updated since the reorganisation. New customer requirements are not yet taken into account.',
        1 => "No document describes the organisation's context or the ISMS scope. Interested parties and their expectations have not been identified.",
    ],
    '27001:2022-5.x' => [
        3 => 'Management has approved the security policy and attends the quarterly security committees. Roles and responsibilities are formally assigned and communicated.',
        2 => 'The security policy is approved by management, but no security committee has been held for six months. Some responsibilities are still assigned informally.',
        1 => 'No evidence of management commitment could be provided. Information security roles and responsibilities are not defined.',
    ],
    '27001:2022-6.x' => [
        3 => 'Risks and opportunities are assessed using a documented methodology and a treatment plan is monitored. Security objectives are measurable and reviewed quarterly.',
        2 => 'A risk assessment has been carried out, but the treatment plan is not monitored regularly. Several security objectives have no measurable indicators.',
        1 => 'No risk assessment has been carried out on the ISMS scope. Information security objectives are not defined.',
    ],
    '27001:2022-7.x' => [
        3 => 'The resources needed for the ISMS are allocated and internal communication is planned. Documented information is controlled, versioned and accessible to the relevant people.',
        2 => 'ISMS resources are identified, but the communication plan is not formalised. Several ISMS documents are neither versioned nor approved.',
        1 => 'No resources are dedicated to running the ISMS. Documented information is neither controlled nor kept up to date.',
    ],
    '27001:2022-7.2' => [
        3 => 'The competences required for each security role are defined and training records are kept. Identified competence gaps are addressed by a monitored training plan.',
        2 => 'Required competences are defined for key roles, but training records are incomplete. Two administrators have not attended the training planned this year.',
        1 => 'The competences required for security-related roles are not defined. No training or qualification records could be presented.',
    ],
    '27001:2022-8.x' => [
        3 => 'ISMS processes are planned and controlled in line with the risk treatment plan. Planned changes are assessed and outsourced processes are controlled.',
        2 => 'The risk treatment plan is being implemented, but several actions are late without reassessment. Outsourced processes are only partially controlled.',
        1 => 'The actions of the risk treatment plan are not implemented. No operational control of ISMS processes could be demonstrated.',
    ],
    '27001:2022-9.2' => [
        3 => 'The internal audit programme covers the whole scope over three years and has been followed. Audit reports are shared with management and findings are tracked.',
        2 => 'An internal audit programme exists, but two audits planned this year were not carried out. Auditor independence is not ensured for some processes.',
        1 => 'No internal audit of the ISMS has been carried out in the last eighteen months. There is no audit programme.',
    ],
    '27001:2022-9.3' => [
        3 => 'The management review took place this year and covers all inputs required by the standard. Resulting decisions and actions are recorded in the minutes and tracked.',
        2 => 'The management review took place, but the minutes do not address audit results or changes in risks. The agreed actions have no deadlines.',
        1 => 'No management review has been held for more than two years. Management does not review ISMS performance.',
    ],
    '27001:2022-10.1' => [
        3 => 'Improvement opportunities are collected and tracked in a dedicated register. Completed improvements are presented at the management review.',
        2 => 'Improvements are made occasionally, but they are not recorded in a register. Their effectiveness is not evaluated.',
        1 => 'No continual improvement of the ISMS could be demonstrated. The same weaknesses are found year after year.',
    ],
    '27001:2022-10.2' => [
        3 => 'Nonconformities are recorded, their causes analysed and corrective actions tracked until closure. The effectiveness of actions is verified.',
        2 => 'Nonconformities are recorded, but root cause analysis is rarely performed. Several corrective actions are overdue without justification.',
        1 => 'Nonconformities are not recorded and no corrective action is tracked. Findings from the last audit are still open.',
    ],
    '27001:2022-5.01' => [
        3 => 'The information security policy is approved by management, published on the intranet and reviewed annually. Topic-specific policies are up to date and communicated to staff.',
        2 => 'The security policy is approved, but its last review is more than two years old. Some topic-specific policies, such as remote working, are missing.',
        1 => 'There is no approved information security policy. Staff are not aware of the applicable security rules.',
    ],
    '27001:2022-5.02' => [
        3 => 'Security roles and responsibilities are defined in job descriptions and a CISO has been appointed. The security organisation chart is up to date and shared.',
        2 => 'A CISO has been appointed, but asset owner responsibilities are not formalised. Job descriptions do not mention security obligations.',
        1 => 'No information security roles or responsibilities are assigned. Nobody is in charge of managing security.',
    ],
    '27001:2022-5.03' => [
        3 => 'A segregation of duties matrix is defined for sensitive processes and checked during access reviews. No conflict was found in the sample tested.',
        2 => 'Segregation of duties is applied to payments but not to system administration. Two administrators can both create and approve accounts.',
        1 => 'No segregation of duties is in place. A single person can initiate, approve and execute sensitive operations.',
    ],
    '27001:2022-5.04' => [
        3 => 'Management requires staff to follow security rules and ensures they are applied. These commitments are restated during annual reviews.',
        2 => 'Management has communicated the security rules, but managers do not check compliance. Contractors are not informed of their obligations.',
        1 => 'Management does not ensure that staff apply security rules. No related communication could be presented.',
    ],
    '27001:2022-5.05' => [
        3 => 'The list of authorities to contact (national CERT, data protection authority, police) is up to date and included in incident procedures. Contact owners are identified.',
        2 => 'A list of authorities exists, but it has not been updated for three years. The people authorised to contact authorities are not designated.',
        1 => 'No list of authorities to contact is available. The organisation does not know whom to notify in the event of a major incident.',
    ],
    '27001:2022-5.06' => [
        3 => 'The security team is a member of a sector CERT and takes part in professional working groups. Information received is shared internally.',
        2 => 'The security team follows a few mailing lists but does not take part in any specialist group. Information is not shared in a structured way.',
        1 => 'No contact is maintained with security special interest groups or forums. The team has no access to industry lessons learned.',
    ],
    '27001:2022-5.07' => [
        3 => 'A threat intelligence feed is collected, analysed and fed into the SOC detection rules. A monthly bulletin is sent to the relevant teams.',
        2 => 'Threat information is received, but it is neither analysed nor used. Detection rules are not updated accordingly.',
        1 => 'No threat intelligence is collected. The organisation has no visibility of the threats targeting it.',
    ],
    '27001:2022-5.08' => [
        3 => 'Security is built into the project methodology with a risk assessment at each milestone. The three sampled projects each have an approved security file.',
        2 => 'The project methodology requires a risk assessment, but one of the three sampled projects did not perform one. Security requirements are not always traced.',
        1 => 'Information security is not considered in project management. None of the sampled projects underwent a risk assessment.',
    ],
    '27001:2022-5.09' => [
        3 => 'The asset inventory is kept in the CMDB, each asset has an owner and the inventory is reviewed every six months. All twenty sampled servers are listed.',
        2 => 'An asset inventory exists, but some network equipment and SaaS applications are missing. Several assets have no designated owner.',
        1 => 'There is no inventory of information assets. The organisation cannot identify the systems it operates.',
    ],
    '27001:2022-5.10' => [
        3 => 'An IT acceptable use policy is signed by all staff. It is reviewed annually and included in the onboarding process.',
        2 => 'An acceptable use policy exists, but about 30% of employees have not signed it. It does not cover the use of generative AI tools.',
        1 => 'No acceptable use rules are defined for information and assets. Staff have never been told what use is permitted.',
    ],
    '27001:2022-5.11' => [
        3 => 'The return of assets is checked at every departure using a signed checklist. All ten sampled leavers returned their equipment.',
        2 => 'A return procedure exists, but two of the ten sampled leavers did not return their laptop. Follow-up is not systematic.',
        1 => 'No asset return procedure is in place. Several former employees still hold company equipment.',
    ],
    '27001:2022-5.12' => [
        3 => 'A four-level classification policy is defined and applied to information. Owners classify their assets and classification is reviewed annually.',
        2 => 'A classification policy exists, but it is only applied to HR documents. Business data is not classified.',
        1 => 'No information classification scheme is defined. Sensitive information is not identified.',
    ],
    '27001:2022-5.13' => [
        3 => 'Documents are labelled according to their classification level using office templates. Removable media and sensitive emails are labelled as well.',
        2 => 'Document templates include labelling, but it is rarely used. Emails and data exports are not labelled.',
        1 => 'No information labelling is performed. A confidential document cannot be told apart from a public one.',
    ],
    '27001:2022-5.14' => [
        3 => 'Information transfer rules are defined by classification and external exchanges use an encrypted platform. Transfer agreements are signed with partners.',
        2 => 'A secure exchange platform is available, but confidential files are still sent by unencrypted email. The rules are not formalised.',
        1 => 'No rules or secure means of information transfer are in place. Sensitive data is exchanged through consumer file-sharing services.',
    ],
    '27001:2022-5.15' => [
        3 => 'The access control policy is based on need-to-know and least privilege. It is applied to all in-scope applications and reviewed annually.',
        2 => 'An access control policy exists, but it is not applied to SaaS applications. Some access is granted without owner approval.',
        1 => 'No access control policy is defined. Access is granted without rules or approval.',
    ],
    '27001:2022-5.16' => [
        3 => 'The identity lifecycle is automated from the HR system, including account creation and deletion. No orphan account was found in the sample tested.',
        2 => 'Account creation is controlled, but deletion relies on a manual request. Twelve accounts of people who have left are still active.',
        1 => 'There is no identity management process. Shared accounts and accounts of former employees are in use.',
    ],
    '27001:2022-5.17' => [
        3 => 'The password policy enforces length and complexity, and multi-factor authentication is enabled for everyone. Initial passwords are delivered securely.',
        2 => 'The password policy is enforced on the directory but not on legacy business applications. Multi-factor authentication is not generalised.',
        1 => 'No rules govern authentication information. Passwords are shared between colleagues and stored in clear text in files.',
    ],
    '27001:2022-5.18' => [
        3 => 'Access rights are granted after manager approval and reviewed every six months. The last review led to the documented removal of unnecessary rights.',
        2 => 'Rights are granted after approval, but the periodic review was not carried out this year. Excessive rights were found after internal transfers.',
        1 => 'Access rights are never reviewed. Employees keep the rights of their previous positions.',
    ],
    '27001:2022-5.19' => [
        3 => 'Critical suppliers are identified and assessed for security before contracting. A security questionnaire is completed and analysed for each of them.',
        2 => 'Critical suppliers are identified, but only half have undergone a security assessment. New suppliers are not systematically assessed.',
        1 => 'No supplier security assessment is carried out. The organisation does not know which suppliers have access to its data.',
    ],
    '27001:2022-5.20' => [
        3 => 'Supplier contracts include a security schedule covering confidentiality, incident notification and the right to audit. The sampled contracts are compliant.',
        2 => 'New contracts include security clauses, but older contracts have not been updated. The right to audit is missing from several critical contracts.',
        1 => 'Supplier contracts contain no information security requirements. No incident notification obligation is defined.',
    ],
    '27001:2022-5.21' => [
        3 => "ICT supply chain security requirements are passed on to suppliers' subcontractors. The provenance of critical components is verified.",
        2 => 'Direct suppliers are controlled, but second-tier subcontracting is not. The provenance of software components is not verified.',
        1 => 'No control of the ICT supply chain is in place. Risks related to subcontractors are not identified.',
    ],
    '27001:2022-5.22' => [
        3 => 'Services from critical suppliers are monitored through quarterly committees and service level indicators. Changes are analysed before being accepted.',
        2 => 'Monitoring committees exist for some suppliers, but security reports are not requested. Service changes are not assessed.',
        1 => 'Supplier services are neither monitored nor reviewed. Changes are accepted without analysis.',
    ],
    '27001:2022-5.23' => [
        3 => 'A cloud usage policy defines requirements for acquiring, using and exiting services. Services in use are inventoried and their certifications checked.',
        2 => 'The main cloud services are controlled, but several SaaS services are used without approval. Reversibility is not addressed in contracts.',
        1 => 'The use of cloud services is not governed. Many services are subscribed directly by business units without any security review.',
    ],
    '27001:2022-5.24' => [
        3 => 'The incident management procedure is documented, roles are defined and an exercise was held this year. The necessary contacts and tools are available.',
        2 => 'An incident management procedure exists, but it has never been tested. Not all incident response roles are assigned.',
        1 => 'No security incident management procedure is defined. The organisation is not prepared to respond to an incident.',
    ],
    '27001:2022-5.25' => [
        3 => 'Security events are assessed against a documented severity grid. Decisions to classify events as incidents are recorded in the ticketing tool.',
        2 => "Events are analysed, but no assessment grid is formalised. Classification decisions depend on the analyst's judgement.",
        1 => 'Security events are neither assessed nor classified. No record of decisions could be presented.',
    ],
    '27001:2022-5.26' => [
        3 => "Incidents are handled according to the procedure, with containment, eradication and communication recorded. The year's three incidents were closed on time.",
        2 => 'Incidents are handled, but not all response actions are recorded. One incident was not reported to management within the required time.',
        1 => 'Incidents are handled case by case without a procedure. No history of past incidents is kept.',
    ],
    '27001:2022-5.27' => [
        3 => 'Every significant incident leads to a documented lessons-learned review. Identified improvement actions are tracked until closure.',
        2 => 'Lessons-learned reviews are held for major incidents, but the identified actions are not tracked. Lessons are not shared.',
        1 => 'No lessons-learned review is held after incidents. The same incidents recur without improvement.',
    ],
    '27001:2022-5.28' => [
        3 => 'An evidence collection procedure ensures chain of custody and integrity of collected items. Forensic tools are available and staff are trained.',
        2 => 'Evidence is collected during incidents, but without integrity guarantees or chain of custody. No procedure is formalised.',
        1 => 'No evidence collection is planned during incidents. Logs are overwritten before any analysis.',
    ],
    '27001:2022-5.29' => [
        3 => 'Continuity plans include maintaining security controls in degraded mode. This year\'s exercise confirmed their effectiveness.',
        2 => 'Continuity plans exist but do not cover maintaining security in degraded mode. Emergency access is not controlled.',
        1 => 'Information security is not considered during disruption. No plan describes the controls to be maintained.',
    ],
    '27001:2022-5.30' => [
        3 => 'The IT recovery plan is based on the BIA and tested annually. RTO and RPO targets were met during the last test.',
        2 => 'An IT recovery plan exists, but it has not been tested for two years. RTOs for critical applications are not validated by the business.',
        1 => 'No IT recovery plan is defined. The organisation does not know its recovery times in the event of a disaster.',
    ],
    '27001:2022-5.31' => [
        3 => 'A register of legal, regulatory and contractual requirements is kept up to date by the legal department. It is reviewed every six months with the CISO.',
        2 => 'The main regulatory requirements are identified, but the register does not include NIS 2. Customer contractual requirements are not listed.',
        1 => 'Applicable legal, regulatory and contractual requirements are not identified. No register exists.',
    ],
    '27001:2022-5.32' => [
        3 => 'Software licences are inventoried and reconciled with installations every quarter. No discrepancy was found during the last check.',
        2 => 'A licence inventory exists, but it is not reconciled with installations. A few installed programs have no licence.',
        1 => 'Licences are not tracked. Unlicensed software is used on many workstations.',
    ],
    '27001:2022-5.33' => [
        3 => 'Record retention periods are defined and applied. Records are protected against loss, falsification and unauthorised access.',
        2 => 'Retention periods are defined for accounting data but not for technical logs. Some records are stored without protection.',
        1 => 'No retention or protection rules are defined for records. Mandatory records have been lost.',
    ],
    '27001:2022-5.34' => [
        3 => 'The record of processing activities is up to date and impact assessments have been carried out for high-risk processing. The DPO is involved in projects.',
        2 => 'The record of processing activities exists, but it is not up to date for new processing. No impact assessment was carried out for video surveillance.',
        1 => 'No record of personal data processing is kept. GDPR obligations are not taken into account.',
    ],
    '27001:2022-5.35' => [
        3 => 'An independent security review was carried out this year by an external firm. Its recommendations are included in the action plan and tracked.',
        2 => 'An independent review was carried out three years ago but has not been repeated. Half of the recommendations have not been addressed.',
        1 => 'No independent review of information security has ever been carried out.',
    ],
    '27001:2022-5.36' => [
        3 => 'Managers regularly check compliance with security policies in their area. Technical compliance checks are automated.',
        2 => 'Compliance checks are carried out occasionally, without planning or traceability. Identified gaps are not followed up.',
        1 => 'Compliance with security policies and standards is never checked. Several obvious gaps were observed during the audit.',
    ],
    '27001:2022-5.37' => [
        3 => 'Operating procedures are documented, versioned and available to the teams. They are reviewed after every significant change.',
        2 => 'Operating procedures exist for servers but not for network equipment. Several procedures are out of date.',
        1 => 'No operating procedure is documented. Know-how relies on a few key people.',
    ],
    '27001:2022-6.01' => [
        3 => 'Background checks are carried out before each hire, in proportion to the risks of the position. The ten sampled files are complete.',
        2 => 'Checks are carried out for managers but not for contractors or temporary staff. Two sampled files are incomplete.',
        1 => 'No background checks are carried out before hiring, including for sensitive positions.',
    ],
    '27001:2022-6.02' => [
        3 => 'Employment contracts include information security responsibilities. Employees sign the IT charter when they join.',
        2 => 'New contracts include a security clause, but contracts signed before 2022 have not been updated. The charter is not attached.',
        1 => 'Employment contracts mention no information security obligations.',
    ],
    '27001:2022-6.03' => [
        3 => 'An annual awareness programme is deployed with a 95% completion rate. Simulated phishing campaigns are run every quarter.',
        2 => 'Awareness training is provided on arrival, but no periodic refresher is organised. The e-learning completion rate is 55%.',
        1 => 'No information security awareness activity is carried out. Staff are unaware of the risks and good practices.',
    ],
    '27001:2022-6.04' => [
        3 => 'The disciplinary process explicitly covers breaches of security rules. It is communicated to staff through the internal regulations.',
        2 => 'The internal regulations mention sanctions, but not breaches of the security policy. The process has never been applied.',
        1 => 'No disciplinary process covers information security breaches.',
    ],
    '27001:2022-6.05' => [
        3 => 'Security obligations that remain after departure are restated during the exit interview. Access is revoked on the day of departure.',
        2 => 'Access is revoked on departure, but confidentiality obligations are not restated. Internal transfers are not taken into account.',
        1 => 'No measures are taken when people leave or change jobs. Former employees\' access remains active.',
    ],
    '27001:2022-6.06' => [
        3 => 'Confidentiality agreements are signed by all staff and contractors. They are reviewed annually by the legal department.',
        2 => 'Employees sign a confidentiality agreement, but contractors do not. The template has not been reviewed for five years.',
        1 => 'No confidentiality agreement is signed by staff or third parties.',
    ],
    '27001:2022-6.07' => [
        3 => 'A remote working policy defines the security rules and remote access goes through a VPN with MFA. Mobile devices are encrypted and managed.',
        2 => 'Remote access is secured by VPN, but multi-factor authentication is not enabled. The remote working policy does not address security.',
        1 => 'Remote working is not governed. Employees access the information system from unmanaged personal devices.',
    ],
    '27001:2022-6.08' => [
        3 => 'A single channel for reporting security events is known to staff. Reports are handled and feedback is given to the reporters.',
        2 => 'A reporting channel exists, but it is little known by staff. Not all reports received are handled.',
        1 => 'No means of reporting security events is available to staff.',
    ],
    '27001:2022-7.01' => [
        3 => 'The physical security perimeter is defined and enforced by fences and controlled entrances. Sensitive areas are clearly identified.',
        2 => 'The perimeter is defined, but an emergency exit is regularly left open. Not all sensitive areas are identified.',
        1 => 'No physical security perimeter is defined. The premises are freely accessible from outside.',
    ],
    '27001:2022-7.02' => [
        3 => 'Access to the premises is controlled by badge and visitors are registered and escorted. Physical access rights are reviewed every six months.',
        2 => 'Access is controlled by badge, but the visitor log is not kept systematically. Badges of people who have left are still active.',
        1 => 'No physical access control is in place. Visitors move freely around the premises.',
    ],
    '27001:2022-7.03' => [
        3 => 'Sensitive offices and rooms are locked and access is restricted. Information about their location is not disclosed.',
        2 => 'The server room is secured, but management and HR offices remain open outside working hours.',
        1 => 'No measures secure offices and rooms. The server room is not locked.',
    ],
    '27001:2022-7.04' => [
        3 => 'The premises are covered by CCTV and intrusion detection connected to a monitoring centre. Alarms are tested monthly.',
        2 => 'CCTV is installed, but recordings are not reviewed. The alarm system has not been tested for a year.',
        1 => 'No physical security monitoring device is installed.',
    ],
    '27001:2022-7.05' => [
        3 => 'Physical and environmental threats are analysed and the server room has fire detection, redundant cooling and a flood sensor.',
        2 => 'The server room has fire detection, but cooling is not redundant. No flood sensor is installed.',
        1 => 'No protection against physical and environmental threats is in place in the server room.',
    ],
    '27001:2022-7.06' => [
        3 => 'Rules for working in secure areas are defined and posted. Third-party work in these areas is always escorted.',
        2 => 'Rules for working in secure areas exist, but contractors sometimes work there unescorted.',
        1 => 'No rules apply to working in secure areas.',
    ],
    '27001:2022-7.07' => [
        3 => 'The clear desk and clear screen policy is applied and automatic locking is set to five minutes. The walk-through found no deviation.',
        2 => 'Automatic screen locking is configured, but the walk-through found confidential documents left on several desks.',
        1 => 'No clear desk or clear screen rule is defined. Many workstations remain unlocked while their users are away.',
    ],
    '27001:2022-7.08' => [
        3 => 'Equipment is sited in protected locations, out of sight and away from environmental risks. Exposed screens have privacy filters.',
        2 => 'Servers are properly sited, but network equipment is located in unlocked technical rooms.',
        1 => 'Equipment is installed without regard to risks. Sensitive equipment is accessible in corridors.',
    ],
    '27001:2022-7.09' => [
        3 => 'Assets used off-site are encrypted, inventoried and covered by usage rules. Mobile staff are trained.',
        2 => 'Laptops are encrypted, but company smartphones are not managed. Off-site usage rules are not formalised.',
        1 => 'No measures protect assets off-site. Laptops are not encrypted.',
    ],
    '27001:2022-7.10' => [
        3 => 'The use of removable media is restricted by a device control solution. Authorised media are encrypted and inventoried.',
        2 => 'A policy covers removable media, but no technical measure blocks unauthorised USB drives.',
        1 => 'The use of removable storage media is not governed. Sensitive data circulates on unencrypted USB drives.',
    ],
    '27001:2022-7.11' => [
        3 => 'The server room is powered by a UPS and a generator tested quarterly. Supporting utilities are redundant.',
        2 => 'A UPS protects the server room, but the generator has not been tested for eighteen months.',
        1 => 'No protection against utility failures is in place. A power cut immediately shuts down the servers.',
    ],
    '27001:2022-7.12' => [
        3 => 'Cabling is protected in closed cable trays and documented. Patch cabinets are locked.',
        2 => 'Cabling is documented, but several patch cabinets are not locked.',
        1 => 'Cabling is neither protected nor documented. Live network sockets are accessible in public areas.',
    ],
    '27001:2022-7.13' => [
        3 => "Equipment undergoes preventive maintenance according to manufacturers' recommendations. Interventions are recorded and maintenance contracts are current.",
        2 => 'Maintenance contracts exist, but several devices are out of support. Interventions are not recorded.',
        1 => 'No equipment maintenance is planned. Equipment is only repaired when it fails.',
    ],
    '27001:2022-7.14' => [
        3 => 'Media are securely wiped or destroyed by a certified provider before disposal. Destruction certificates are kept.',
        2 => 'Server disks are destroyed, but recycled workstations are not securely wiped.',
        1 => 'No secure disposal procedure is applied. Computers are given away without data being erased.',
    ],
    '27001:2022-8.01' => [
        3 => 'User endpoints are managed by an MDM solution, encrypted and kept up to date. Device compliance is checked before access to the information system.',
        2 => 'Workstations are managed, but mobile devices are not. Encryption is not enabled on 15% of workstations.',
        1 => 'User endpoints are neither managed nor secured. Users are administrators of their own workstations.',
    ],
    '27001:2022-8.02' => [
        3 => 'Privileged access rights are assigned individually, through a bastion, and reviewed quarterly. Privileged accounts are separate from office accounts.',
        2 => 'Privileged accounts are identified, but some administrators use their admin account for office work. No review is carried out.',
        1 => 'Privileged access rights are not controlled. Generic administrator accounts are shared by several people.',
    ],
    '27001:2022-8.03' => [
        3 => 'Access to information is restricted according to the access control policy in all applications. Network shares are assigned to named users.',
        2 => 'Business applications restrict access, but several network shares are open to all users.',
        1 => 'Access to information is not restricted. All users can view sensitive data.',
    ],
    '27001:2022-8.04' => [
        3 => 'Source code is stored in a version control system with restricted and reviewed rights. Changes require peer review.',
        2 => 'Source code is versioned, but all developers have access to every repository, including the most sensitive ones.',
        1 => 'Access to source code is not controlled. Code is shared on open network folders.',
    ],
    '27001:2022-8.05' => [
        3 => 'Multi-factor authentication is enforced for all remote access and privileged accounts. Authentication mechanisms are replay-resistant.',
        2 => 'Multi-factor authentication is enabled for email, but not for the VPN or administration consoles.',
        1 => 'No strong authentication is implemented. Remote access relies on a password only.',
    ],
    '27001:2022-8.06' => [
        3 => 'System capacity is monitored with alert thresholds and a capacity plan is reviewed annually. No saturation incident has occurred.',
        2 => 'Capacity alerts are configured, but no capacity plan has been drawn up. Two storage saturations occurred this year.',
        1 => 'System capacity is not monitored. Saturation is discovered by users.',
    ],
    '27001:2022-8.07' => [
        3 => 'An EDR solution is deployed on 100% of workstations and servers, with up-to-date signatures. Alerts are handled by the SOC.',
        2 => 'Antivirus is deployed on workstations but not on Linux servers. Alerts are not monitored.',
        1 => 'No malware protection is deployed on a significant part of the estate.',
    ],
    '27001:2022-8.08' => [
        3 => 'Vulnerabilities are detected by weekly scans and fixed within deadlines set by severity. No critical vulnerability has been open for more than fifteen days.',
        2 => 'Vulnerability scans are performed, but critical patches are applied several months late.',
        1 => 'No technical vulnerability management is in place. Internet-facing systems have known critical vulnerabilities.',
    ],
    '27001:2022-8.09' => [
        3 => 'Baseline configurations are defined and deployed automatically. Configuration drift is detected and corrected.',
        2 => 'Baseline configurations exist for workstations but not for servers. Drift is not checked.',
        1 => 'No baseline configuration is defined. Systems are installed with their default settings.',
    ],
    '27001:2022-8.10' => [
        3 => 'Information is deleted at the end of its retention period according to a documented procedure. Deletions are recorded.',
        2 => 'Retention periods are defined, but data is not deleted when they expire. Customer data more than ten years old is still kept.',
        1 => 'Information is never deleted. Data is kept indefinitely.',
    ],
    '27001:2022-8.11' => [
        3 => 'Personal data is masked or pseudonymised in non-production environments. Masking is automated when databases are copied.',
        2 => 'Masking is applied to the main application, but other test environments contain real data.',
        1 => 'No data masking is performed. Test environments contain full copies of production.',
    ],
    '27001:2022-8.12' => [
        3 => 'A DLP solution monitors email and file transfer flows. Alerts are analysed and potential leaks are blocked.',
        2 => 'DLP rules are configured on email, but transfers to personal cloud storage are not monitored.',
        1 => 'No data leakage prevention measure is in place.',
    ],
    '27001:2022-8.13' => [
        3 => 'Backups are performed daily, including an offline copy. Quarterly restore tests are successful.',
        2 => 'Backups are performed daily, but no restore test has been carried out this year. No copy is kept offline.',
        1 => 'Backups are not performed regularly. A recent restore failed because no usable backup was available.',
    ],
    '27001:2022-8.14' => [
        3 => 'Critical systems are redundant across two sites and failover is tested annually. The last test met the availability targets.',
        2 => 'Critical servers are redundant, but network equipment is a single point of failure.',
        1 => 'No redundancy of information processing facilities is in place. A single server failure stops critical services.',
    ],
    '27001:2022-8.15' => [
        3 => 'Logs from critical systems are centralised in a SIEM, protected and kept for one year. Administration logs are included.',
        2 => 'Logs are collected for Windows servers but not for business applications. Retention is thirty days.',
        1 => 'No centralised logging is in place. Local logs are overwritten after a few days.',
    ],
    '27001:2022-8.16' => [
        3 => 'The SOC monitors networks and systems 24/7 and handles alerts according to defined procedures. Detection rules are reviewed monthly.',
        2 => 'The SIEM generates alerts, but they are only reviewed during business hours. Many alerts are not handled.',
        1 => 'No monitoring of networks and systems is performed.',
    ],
    '27001:2022-8.17' => [
        3 => 'All systems are synchronised with reference NTP time sources. Drift is monitored.',
        2 => 'Servers are synchronised, but several network devices are several minutes off.',
        1 => 'System clocks are not synchronised, which prevents log correlation.',
    ],
    '27001:2022-8.18' => [
        3 => 'The use of privileged utility programs is restricted to administrators and logged. Their list is reviewed annually.',
        2 => 'Privileged utilities are restricted on servers but available on some user workstations.',
        1 => 'The use of privileged utility programs is not controlled.',
    ],
    '27001:2022-8.19' => [
        3 => 'Software installation is restricted to administrators through an approved catalogue. Installations on production systems follow the change process.',
        2 => 'Users cannot install software, but installations in production are made outside the change process.',
        1 => 'Users freely install software, including on servers.',
    ],
    '27001:2022-8.20' => [
        3 => 'Networks are secured and managed according to documented rules. Devices are hardened and their configurations backed up.',
        2 => 'Network devices are managed, but some still use unencrypted protocols such as Telnet.',
        1 => 'No security measures are applied to networks. Devices still use their default passwords.',
    ],
    '27001:2022-8.21' => [
        3 => "Security requirements for network services are defined in carriers' contracts. Service levels are monitored.",
        2 => 'Network services are under contract, but no security requirements are formalised.',
        1 => 'Security mechanisms of network services are neither identified nor controlled.',
    ],
    '27001:2022-8.22' => [
        3 => 'A proxy filters Internet access by defined categories and blocks malicious sites. Browsing logs are kept.',
        2 => 'Web filtering is in place on site, but remote workstations are not covered.',
        1 => 'No web filtering is implemented.',
    ],
    '27001:2022-8.23' => [
        3 => 'The network is segmented into security zones separated by firewalls. Flows between zones are limited to what is strictly necessary and reviewed.',
        2 => 'The network is segmented, but filtering rules between zones are too permissive. Several "any-any" rules were found.',
        1 => 'The network is not segregated. User workstations can reach production servers directly.',
    ],
    '27001:2022-8.24' => [
        3 => 'A cryptography policy defines approved algorithms and key management. Sensitive data is encrypted at rest and in transit.',
        2 => 'External flows are encrypted, but some sensitive databases are not encrypted at rest. No key management policy is formalised.',
        1 => 'No cryptographic measures are implemented. Obsolete protocols and expired certificates are in use.',
    ],
    '27001:2022-8.25' => [
        3 => 'A secure development lifecycle is defined and applied, with automated code analysis in the CI/CD pipeline. Developers are trained.',
        2 => 'Secure development rules exist, but code analysis is not integrated into the CI/CD pipeline.',
        1 => 'No secure development rules are defined.',
    ],
    '27001:2022-8.26' => [
        3 => 'Security requirements are identified and approved when each application is designed. They are traced through to testing.',
        2 => 'Security requirements are defined for new applications, but not for major changes.',
        1 => 'Application security requirements are not identified.',
    ],
    '27001:2022-8.27' => [
        3 => 'Secure architecture principles are documented and applied during architecture reviews. New systems follow defence in depth.',
        2 => 'Architecture principles exist, but architecture reviews are not systematically carried out.',
        1 => 'No secure engineering principles are defined for system design.',
    ],
    '27001:2022-8.28' => [
        3 => 'Secure coding rules are applied and checked through code review and static analysis. Vulnerable dependencies are detected automatically.',
        2 => 'Secure coding rules exist, but code reviews are not systematic. Dependencies are not analysed.',
        1 => 'No secure coding practice is applied. SQL injections were identified in the code.',
    ],
    '27001:2022-8.29' => [
        3 => 'Security tests and a penetration test are performed before every major release. Vulnerabilities are fixed before acceptance.',
        2 => 'Penetration tests are performed annually, but not before releases. Some vulnerabilities are accepted without analysis.',
        1 => 'No security testing is performed in the development lifecycle.',
    ],
    '27001:2022-8.30' => [
        3 => 'Outsourced development is governed by contractual security requirements and code reviews. Deliverables are tested before acceptance.',
        2 => 'Outsourced development contracts include security requirements, but deliverables are not checked.',
        1 => 'Outsourced development is subject to no security requirement or control.',
    ],
    '27001:2022-8.31' => [
        3 => 'Development, test and production environments are logically separated with distinct access. Developers have no access to production.',
        2 => 'Environments are separated, but several developers still have access to production.',
        1 => 'Development, test and production environments are not separated.',
    ],
    '27001:2022-8.32' => [
        3 => 'Changes are managed through a formal process with impact analysis, approval and rollback. All sampled changes are recorded.',
        2 => 'A change management process exists, but several emergency changes were made without retrospective approval.',
        1 => 'No change management process is in place. Changes are made directly in production.',
    ],
    '27001:2022-8.33' => [
        3 => 'Test data is selected and protected, with production data anonymised before use. Test datasets are deleted after use.',
        2 => 'Test data is anonymised for most applications, but not for the billing application.',
        1 => 'Production data is used as-is for testing, without any protection.',
    ],
    '27001:2022-8.34' => [
        3 => "Audits and tests on operational systems are planned and approved to limit impact. Auditors' access is read-only and revoked afterwards.",
        2 => 'Tests are planned, but access granted to auditors is not revoked at the end of the engagement.',
        1 => 'Audits and tests are performed on operational systems without planning or oversight.',
    ],
];
