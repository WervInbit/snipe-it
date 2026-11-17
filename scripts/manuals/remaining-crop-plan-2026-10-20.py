"""Measured CSS crops for the retained remaining-guide round; no screenshot text edits."""
import json
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
ROUND = ROOT / 'resources/manuals/operator-guides/review-rounds/2026-10-20-remaining-guides'
assert not (ROUND / 'manifest.sha256').exists(), 'Sealed round'
PLAN = {}


def state(name):
    return json.loads((ROUND / 'evidence' / (name + '.json')).read_text(encoding='utf-8-sig'))


def union(items, padding=5):
    a = [i['bounds'] if 'bounds' in i else i for i in items]
    x = min(b['x'] for b in a); y = min(b['y'] for b in a)
    right = max(b['x'] + b['w'] for b in a); bottom = max(b['y'] + b['h'] for b in a)
    return [max(0, x-padding), max(0, y-padding), right-x+2*padding, bottom-y+2*padding]


def add(key, name, rect, job, marks=None):
    PLAN[key] = dict(source=name, bounds=rect, job=job, marks=marks or [])


def groups(name, prefixes, padding=5):
    es=state(name)['elements']
    return union([next(e for e in es if e['tag']=='DIV' and 'form-group' in e['classes'] and e['text'].startswith(p)) for p in prefixes],padding)


def byid(name, value, pad=5):
    return union([next(e for e in state(name)['elements'] if e['id']==value)],pad)


add('cat-nav','CAT-NAV',[0,215,230,245],'Locate Asset modellen, Attributes and Component Definitions under the expanded Instellingen menu.')
add('models','CAT-MODELS-FILTERED',[235,60,1025,295],'Recognize Asset modellen, applied search and the complete matching product/generation row.')
create=next(e for e in state('CAT-MODELS-FILTERED')['elements'] if e['tag']=='BUTTON' and e['name']=='btnAdd')['bounds']
add('models-create-action','CAT-MODELS-FILTERED',[235,60,1025,295],'Locate the small Basismodel create icon among other model toolbar actions.',[dict(**create,shape='ellipse',padding=3,target='Nieuw Basismodel',reason='Distinguish the small plus icon from the neighboring toolbar actions.')])
add('model','CAT-MODEL',[65,65,900,210],'Read the owning Basismodel and complete exact model-number row.')
add('model-entry','CAT-MODEL',[65,65,900,100],'Locate complete Create Model Number under the same HP Basismodel breadcrumb and Model Numbers heading.')
add('model-info','CAT-MODEL',[985,117,280,103],'Read category and manufacturer in Meer Info on the same Basismodel.')
add('numbers','CAT-NUMBERS',[65,65,1200,182],'Read the global Model Numbers heading, complete matching code row and exact-code search together.')
add('model-create','CAT-MODEL-FILLED',[425,120,660,230],'Recognize only reusable product identity, category, manufacturer and the save action.')
add('number-create','CAT-NUMBER-FILLED',[425,190,660,315],'Read the exact code, Aa, default configuration label and Opslaan together.')
model_number=next(e for e in state('CAT-SPEC')['elements'] if e['tag']=='DIV' and 'form-group' in e['classes'] and e['text'].startswith('Model Number'))['bounds']
add('spec-top','CAT-SPEC',[model_number['x']+10,175,model_number['w']-20,165],'Verify the HP heading, Model Number selector and complete helper line after the adjacent exact-code row established navigation.')
add('spec-attribute','CAT-SPEC-ATTRIBUTE',[425,347,650,430],'Recognize available/selected attributes, search and the selected direct value.')
search=next(e for e in state('CAT-SPEC')['elements'] if e['tag']=='INPUT' and 'js-attribute-search' in e['classes'])['bounds']
value=next(e for e in state('CAT-SPEC')['elements'] if e['id']=='attribute_1')['bounds']
add('spec-available','CAT-SPEC',[search['x']-5,search['y']-32,value['x']+value['w']-search['x']+10,214],'Recognize all three Edit Spec columns and real plus controls for unused definitions; no arbitrary row is marked.')
add('spec-detail','CAT-SPEC',[value['x']-6,search['y']-32,value['w']+12,value['y']+value['h']-search['y']+37],'Read complete Attribute Details context and the selected direct value.')
rows=[e for e in state('CAT-SPEC')['elements'] if 'component-template-row' in e['classes'] and e['tag']=='DIV']
ram=rows[1]
add('spec-component','CAT-SPEC',union([ram],8),'Read one complete RAM baseline row with definition, quantity and derived attributes.')
first=rows[0]
add('spec-children','CAT-SPEC',union([first],8),'Read component-derived attributes and the motherboard expected child structure.')
added=[e for e in state('CAT-SPEC-ADD')['elements'] if 'component-template-row' in e['classes'] and e['tag']=='DIV'][-1]
add('spec-add','CAT-SPEC-ADD',union([added],8),'Recognize the newly selected unsaved expected-component row and quantity.')
save=next(e for e in state('CAT-SPEC')['elements'] if e['id']=='submit_button')
add('spec-save','CAT-SPEC',[475,save['bounds']['y']-10,580,55],'Read complete Opslaan under the same Edit Spec form; preceding chapters establish the owning form.')
add('attrs','ATTR-LIST',[65,65,1200,290],'Compare an existing reusable attribute in the searchable Attributes list.')
add('attr-identity','ATTR-NUMERIC-CREATE',groups('ATTR-NUMERIC-CREATE',['Label','Key','Datatype','Unit']), 'Read new attribute identity, generated key, numeric datatype and unit.')
add('attr-behavior','ATTR-NUMERIC-CREATE',groups('ATTR-NUMERIC-CREATE',['Category Scope','Required for category','Component Spec Display']), 'Read category scope, deliberate behavior flags and display mode as one form group.')
add('attr-aggregation','ATTR-NUMERIC-CREATE',groups('ATTR-NUMERIC-CREATE',['Numeric Component Aggregation']), 'Recognize aggregation at a numeric definition before the first save.')
add('attr-constraints','ATTR-NUMERIC-CREATE',groups('ATTR-NUMERIC-CREATE',['Constraints']), 'Read Minimum, Maximum and Step together; ordinary Regex remains empty.')
op=next(e for e in state('ATTR-ENUM')['elements'] if e['tag']=='DIV' and e['text'].startswith('Options') and 'form-group' in e['classes'])['bounds']
add('attr-options','ATTR-ENUM-NEW',groups('ATTR-ENUM-NEW',['Options']), 'Read one client-only enum option, Value/Label/Sort/Active entry fields and the complete Add to list action; unsaved alternative example.')
submit=next(e for e in state('ATTR-NUMERIC-CREATE')['elements'] if e['id']=='submit_button')['bounds']
add('attr-save','ATTR-NUMERIC-CREATE',[425,submit['y']-210,660,260],'Recognize complete numeric constraints and the Create control after reviewing the form.')
add('attr-result','ATTR-NUMERIC-RESULT',[415,185,680,112], 'Verify the full Label field on the actually saved definition; reopen the remaining settings as instructed.')
add('attr-distinct','ATTR-DISTINCT',groups('ATTR-DISTINCT',['Numeric Component Aggregation']), 'Read Keep distinct values on the existing Geheugensnelheid definition.')
add('definitions','DEF-LIST',[60,60,1210,188],'Search and compare component identity, Instances, Templates and Active state.')
add('def-identity','DEF-RAM',[70,95,1185,510],'Read reusable RAM definition identity and display label; no physical tag or serial.')
child=next(e for e in state('DEF-PARENT')['elements'] if e['tag']=='DIV' and e['classes']=='panel panel-default' and e['text'].startswith('Component Definition'))['bounds']
section=next(e for e in state('DEF-PARENT')['elements'] if e['tag']=='DIV' and e['text'].startswith('Expected Subcomponents') and e['classes']=='form-group')['bounds']
add('def-children','DEF-PARENT',[child['x']-5,section['y']-5,child['w']+10,child['y']+child['h']-section['y']+10],'Read the section context and one complete expected-child row: definition, quantity, Required and Notes.')
add('def-values','DEF-RAM',[75,940,980,545],'Read both RAM attribute contributions, valid values and display/label flags.')
add('def-save','DEF-RAM',[70,1220,1185,400],'Locate the contribution context and Save Changes without a simulated overlap warning.')
submit=next(e for e in state('DEF-RAM')['elements'] if e['tag']=='BUTTON' and e['text'].strip()=='Save Changes')['bounds']
add('def-save-action','DEF-RAM',[submit['x']-5,submit['y']-9,260,52],'Locate complete Save Changes and Cancel under the same contribution form.')
add('maint-numbers','MAINT-NUMBERS',[65,65,1200,245],'Recognize current global model-number maintenance and the exact code.')
add('maint-number-edit','MAINT-NUMBER-EDIT',[415,510,635,207],'Read complete Status, default selection and Opslaan together in the real global edit form; identity established by the selected exact code.')
add('maint-number-default','MAINT-NUMBER-EDIT',[475,610,580,108],'Read the complete default checkbox text and Opslaan; the adjacent instructions identify the selected exact code and Active prerequisite.')
add('maint-attr-use','MAINT-ATTR',[265,155,800,200],'Read actual in-use warning before changing an attribute definition.')
add('maint-aggregation','MAINT-ATTR',groups('MAINT-ATTR',['Numeric Component Aggregation']), 'Read the current in-use aggregation choice and lifecycle warning before a maintenance change.')
add('maint-attrs','MAINT-ATTR-LIST',[70,147,1190,141],'Locate Hide/Unhide and current field lifecycle in the Attributes list.')
op=next(e for e in state('MAINT-ENUM')['elements'] if e['tag']=='DIV' and e['text'].startswith('Options') and 'form-group' in e['classes'])['bounds']
option_rows=[e for e in state('MAINT-ENUM')['elements'] if e['tag']=='TR']
add('maint-options','MAINT-ENUM',[475,option_rows[0]['bounds']['y']-34,580,option_rows[1]['bounds']['y']+option_rows[1]['bounds']['h']-option_rows[0]['bounds']['y']+37],'Read full Value/Label/Sort/Active/Remove column headings and two saved rows; definition counts are not per-option evidence.')
alert=next(e for e in state('MAINT-ENUM')['elements'] if e['tag']=='DIV' and 'alert-warning' in e['classes'])
add('maint-enum-inuse','MAINT-ENUM',union([alert],5),'Read the entire actual Geheugentype in-use warning and its definition-level counts.')
add('maint-definitions','MAINT-DEF-LIST',[60,110,1210,193],'Locate definition lifecycle actions with use counts; no browser Delete route.')
add('maint-child','MAINT-DEF-PARENT',[70,612,1185,365],'Recognize Remove on the intended stored expected-child row.')
add('maint-contribution','MAINT-DEF-RAM',[70,940,1185,310],'Recognize Remove on the intended saved attribute contribution.')
add('maint-def-save','MAINT-DEF-RAM',[70,1455,1185,175],'Recognize Save Changes; row removal remains unsaved until this action.')
child=next(e for e in state('MAINT-DEF-PARENT')['elements'] if e['tag']=='DIV' and e['classes']=='panel panel-default' and e['text'].startswith('Component Definition'))
value=next(e for e in state('MAINT-DEF-RAM')['elements'] if e['tag']=='DIV' and e['classes']=='panel panel-default' and e['text'].startswith('Attribute'))
add('maint-child-row','MAINT-DEF-PARENT',union([child],5),'Read one complete saved child row, including its real Admin Remove control.')
add('maint-contribution-row','MAINT-DEF-RAM',union([value],5),'Read one complete saved contribution row, including its real Admin Remove control.')
add('maint-save-action','MAINT-DEF-RAM',[70,1603,260,51],'Locate complete Save Changes and Cancel at the bottom; paired with the same form rows.')
add('maint-spec-attribute','MAINT-SPEC',[540,397,515,146],'Read Selected Attributes and Attribute Details, the exact selected direct fact, value and real Admin X action.')
mrows=[e for e in state('MAINT-SPEC')['elements'] if e['tag']=='DIV' and 'component-template-row' in e['classes']]
add('maint-spec-component','MAINT-SPEC',union([mrows[1]],5),'Read the complete saved RAM baseline row with its real Admin Remove control.')
button=next(e for e in state('MAINT-SPEC')['elements'] if e['id']=='submit_button')['bounds']
add('maint-spec-save','MAINT-SPEC',[475,button['y']-10,580,55],'Read complete Opslaan on the actual Admin Edit Spec form after removing a saved row.')

# Administration and floor crops are similarly bounded around one recognition job.
add('usr-new','USR-NEW-IDENTITY',[280,170,650,395],'Read the actual new-user identity, password controls and login flag without any secret.')
add('usr-credentials','USR-NEW-IDENTITY',[275,350,670,160],'Locate complete Genereer, password confirmation and login controls; no secret.')
add('usr-reset','USR-RESET-CONTROL',[275,350,670,165],'Locate complete Genereer, both password fields and unchanged login flag without exposing a value.')
add('usr-group','USR-GROUP-CHOSEN',[415,2149,430,112], 'Read group membership only in the Superadmin view after Optionele informatie.')
add('usr-saved','USR-GROUP-SAVED',[78,261,480,115],'Verify the same saved person, username and group together.')
add('usr-disable','USR-DISABLE-FORM',[475,452,580,54],'Recognize the complete native Deze gebruiker kan inloggen control cleared on the existing user form.')
add('usr-disabled','USR-DISABLED',[78,525,480,80],'Verify the actually saved Login ingeschakeld: Nee in the Info tab.')
add('usr-detail','USR-GROUP-SAVED',[990,516,255,40],'Locate the complete Gebruiker aanpassen action in the right sidebar; paired identity provides orientation.')
add('usr-nav','USR-NAV',[0,430,230,145],'Locate Personen > Toon Alles in the expanded left navigation.')
add('usr-list','USR-NAV',[770,115,495,125],'Locate the user-list search and Nieuwe aanmaken after Personen > Toon Alles.')
add('usr-permissions','USR-PERMISSIONS',[296,643,738,55],'Recognize complete View and Create permission rows within Assets, under the separately shown column headings.')
add('usr-permissions-head','USR-PERMISSIONS',[265,165,780,92],'Recognize complete Informatie/Machtigingen tabs and the three native permission column headings.')
add('usr-assets','USR-PERMISSIONS',[296,643,738,185],'Recognize the Assets Create and Edit Quality Grade rows as different action grants.')
add('group-list','GROUP-LIST',[65,115,1200,420],'Find Groepen, search and compare a group before choosing reuse, edit or create.')
link=next(e for e in state('GROUP-NAV')['links'] if e['text'].startswith('Groepen') and e['href'].endswith('/admin/groups'))['bounds']
add('group-nav','GROUP-NAV',[max(0,link['x']-15),link['y']-60,link['w']+30,link['h']+70],'Recognize the complete native Groepen icon and label in Beheerder Instellingen after opening the top gear icon.')
gear=state('GROUP-NAV')['notes']['adminGearIcon']
add('group-gear','GROUP-NAV',[900,45,380,60],'Locate the small top-right Beheerder Instellingen gear next to the account menu.',[dict(**gear,shape='ellipse',padding=3,target='Beheerder Instellingen',reason='Locate the small unlabeled administration gear beside the signed-in account menu.')])
add('group-top','GROUP-EDIT',groups('GROUP-EDIT',['Groepsnaam']),'Read the complete same custom group name without unrelated fixture notes or other settings.')
modelrow=next(e for e in state('GROUP-EDIT')['elements'] if e['tag']=='TR' and e['text'].startswith('Models\n'))['bounds']
editrow=next(e for e in state('GROUP-EDIT')['elements'] if e['tag']=='TR' and e['text'].startswith('Edit\n') and modelrow['y']<e['bounds']['y']<modelrow['y']+300)
add('group-models','GROUP-EDIT',union([modelrow,editrow],5),'Read complete Models View/Create/Edit controls for a minimal read-only custom group.')
add('group-detail','GROUP-DETAIL',[980,163,285,155],'Read all actually stored grants on the custom group detail page.')
status=next(e for e in state('STATUS-EDIT')['elements'] if e['tag']=='TR' and e['text'].startswith('Refurbisher'))['bounds']
add('status-access','STATUS-EDIT',[475,status['y']-140,580,285],'Distinguish Zien from Kiezen / gebruiken for groups on the correct status label.')
status_choices={e['name']:e['bounds'] for e in state('STATUS-USER-TARGET')['elements'] if e['tag']=='SELECT' and (e['name'] or '').startswith('new_status_access_user_')}
start=status_choices['new_status_access_user_id'];end=status_choices['new_status_access_user_select']
add('status-user','STATUS-USER-TARGET',[start['x']-5,end['y']-8,end['x']+end['w']-start['x']+10,end['h']+16],'Recognize the same selected user and complete separate Zien / Kiezen controls; unsaved example, no rule submitted.')

for name in ['AC-MOBILE-MENU','AC-MOBILE-FORM','CMP-EXISTING-SELECTED','CMP-TRAY-RESULT']:
    assert (ROUND/'evidence'/(name+'.json')).exists(),name

add('ac-menu','AC-MOBILE-MENU',[150,45,250,225],'Recognize the compact signed-in account menu and Wachtwoord wijzigen.')
add('ac-form','AC-MOBILE-FORM',[12,200,391,280],'Read all three password fields, repeated Nieuw wachtwoord label and visible password rule.')
add('ac-save','AC-MOBILE-FORM',[10,485,395,75],'Recognize the complete native Opslaan action under the three password fields.')
alert=next(e for e in state('AC-OWN-SAVED')['elements'] if e['tag']=='DIV' and 'alert-success' in e['classes'])['bounds']
add('ac-success','AC-OWN-SAVED',[alert['x'],alert['y'],600,alert['h']],'Read the complete genuine password-success message as an anonymous control detail; the account heading is outside this job.')
add('reg-entry','REG-DASH',[210,305,195,150],'Locate Nieuwe Asset on the Supervisor dashboard before starting registration.')
add('reg-identity','REG-FORM',groups('REG-FORM',['Assettag','Serienummer']),'Read proposed reserved tag, Unlock/Aa and the physical serial entry.')
add('reg-model','REG-FORM',groups('REG-FORM',['Categorie','Model']),'Recognize category and exact model-number selection as one check.')
add('reg-status','REG-FORM',groups('REG-FORM',['Status\n']),'Read Status and required Statusnotitie before saving once.')
add('reg-saved','REG-SAVED',[12,245,391,300],'Read the actual saved tag, serial and model; Asset openen goes to printer and size selection.')
add('reg-title','REG-ASSET',[12,183,391,92],'Verify the same saved tag and model on the asset before printing and after scanning.')
add('reg-saved-identity','REG-SAVED',[20,340,260,102],'Read complete tag and serial in the result row, paired with the same asset title for model recognition.')
qr=next(e for e in state('REG-ASSET')['elements'] if e['id']=='asset-template-picker')['bounds']
add('reg-print','REG-ASSET',[47,qr['y']-220,321,470],'Read QR preview, template, visible print size, printer choice and Print QR label; no print sent.')
add('reg-print-controls','REG-ASSET',[47,qr['y']-30,321,245],'Read template, visible size, printer and the full Print QR label action with native field context.')
add('reg-preview','REG-ASSET',[101,qr['y']-215,210,187],'Recognize the real QR preview above the matching template and printer controls.')
entry=next(e for e in state('CMP-ENTRY')['elements'] if e['tag']=='UL')['bounds']
add('cmp-entry','CMP-ENTRY',[15,entry['y']-5,385,170],'Locate Components among the icon tabs and Add / Install Component below it.')
add('cmp-new-entry','CMP-NEW-ENTRY',[20,520,375,170],'Locate Show New Component Form in the New Component panel.')
add('cmp-definition','CMP-NEW-DEFINITION',[20,810,375,340],'Read Use Component Definition and its definition, serial and condition fields as one complete group.')
add('cmp-custom','CMP-NEW-CUSTOM',[20,810,375,315],'Read Custom Component and its name, serial and condition as the explicit alternative.')
add('cmp-create','CMP-NEW-DEFINITION',[20,1430,375,60],'Locate the complete Create And Install action after the paired form and placement check.')
card=next(e for e in state('CMP-NEW-RESULT')['elements'] if e['tag']=='ARTICLE' and 'GUIDE-CMP-20261020-01' in e['text'])['bounds']
add('cmp-result','CMP-NEW-RESULT',[card['x'],card['y'],card['w'],130],'Verify the complete Tracked/name/tag/serial identity group; category and condition are outside this recognition job.')
add('cmp-to-tray','CMP-NEW-RESULT',[card['x'],card['y']+255,card['w'],57],'Locate the complete Naar tray action on the same identified component card.')
modal=next(e for e in state('CMP-TRAY-CONFIRM')['elements'] if 'modal-content' in e['classes'])['bounds']
add('cmp-tray-confirm','CMP-TRAY-CONFIRM',[modal['x'],modal['y'],modal['w'],290],'Read the intended component and locked serial in the actual confirmation dialog.')
add('cmp-tray-submit','CMP-TRAY-CONFIRM',[modal['x'],modal['y']+modal['h']-85,modal['w'],85],'Recognize the complete one-time confirmation action in the same dialog.')
add('cmp-existing','CMP-EXISTING-SELECTED',[20,430,375,220],'Read the selected existing tray component with its tag and complete Install action.')
add('cmp-tray-result','CMP-TRAY-RESULT',[15,400,385,355],'Verify the same component identity, In Tray and no asset on the actual saved detail.')
add('cmp-tray-status','CMP-TRAY-DETAIL',[20,438,375,42.3],'Read the complete saved Status: In Tray pair before the following Type field.')
add('cmp-tray-asset','CMP-TRAY-DETAIL',[15,705,385,86.5],'Read Huidige > Asset: N.v.t. through its complete value line, before the next location field.')
assumed=next(e for e in state('CMP-NEW-RESULT')['elements'] if e['tag']=='ARTICLE' and e['text'].startswith('RAM 8GB DDR4\nModel baseline'))['bounds']
add('cmp-assumed','CMP-NEW-RESULT',[assumed['x'],assumed['y'],assumed['w'],164],'Read Assumed and Model baseline on a separate expectation beside the extra Tracked record.')
alert=next(e for e in state('MAINT-INUSE')['elements'] if e['tag']=='DIV' and 'alert-warning' in e['classes'])
add('maint-inuse','MAINT-INUSE',union([alert],5),'Read the complete genuine in-use warning on Werkgeheugen before a change affecting component values.')
aggregation=groups('MAINT-INUSE',['Numeric Component Aggregation'])
aggregation_control=next(e for e in state('MAINT-INUSE')['elements'] if e['tag']=='SELECT' and e['text'].startswith('Sum values by quantity'))
add('maint-inuse-aggregation','MAINT-INUSE',union([aggregation_control],5), 'Read existing Sum in the complete native select; the adjacent instruction identifies Numeric Component Aggregation and the in-use form is established above.')
button=next(e for e in state('GROUP-EDIT')['elements'] if e['id']=='submit_button')['bounds']
add('group-save','GROUP-EDIT',[button['x']-260,button['y']-20,365,80],'Locate Opslaan at the bottom of the same group form.')
add('group-overview','GROUP-DETAIL',[65,113,1200,145],'Recognize the same named group and complete Groep bewerken action with member-list context.')

target=next(e for e in state('CMP-ENTRY')['links'] if e['href']=='#components')['bounds']
PLAN['cmp-entry']['marks']=[dict(**target,shape='ellipse',padding=2,target='Componenten tab',reason='Select the component icon among unlabeled asset-tab icons.')]

(ROUND/'crop-plan.json').write_text(json.dumps(PLAN,indent=2)+'\n',encoding='utf-8')
print(len(PLAN),'measured recognition crops')
