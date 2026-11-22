"""Run only against a dedicated test database: python3 tests/security.py URL."""
import sys, json, re, urllib.request, urllib.parse, urllib.error, http.cookiejar
base=sys.argv[1].rstrip('/')
class NoRedirect(urllib.request.HTTPRedirectHandler):
 def redirect_request(self,*args): return None
client=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()),NoRedirect())
def request(path,data=None,token=None):
 headers={}
 if token: headers['X-CSRF-Token']=token
 if isinstance(data,dict): data=urllib.parse.urlencode(data).encode()
 try: r=client.open(urllib.request.Request(base+'/'+path,data=data,headers=headers))
 except urllib.error.HTTPError as e: r=e
 return r.code,r.read().decode()
def check(label,condition):
 assert condition,label
 print('PASS',label)
def csrf():
 status,body=request('index.php'); check('HTML list',status==200)
 return re.search('name="csrf_token" value="([a-f0-9]+)"',body)[1]
def event(i): return json.loads(request(f'api.php?resource=events&id={i}')[1])['data']
def count(): return len(json.loads(request('api.php?resource=events')[1])['data'])
t=csrf(); original=count()
fields={'title':'<script>alert(1)</script>','venue':'SECURITY_TEST','event_date':'2027-01-01 18:00:00','seats_left':'2'}
check('API missing CSRF',request('api.php?resource=events',fields)[0]==403)
check('No insert after rejected CSRF',count()==original)
code,body=request('api.php?resource=events',fields,t);check('Create API',code==201)
i=json.loads(body)['data']['id'];check('JSON title unescaped',event(i)['title']==fields['title'])
t=csrf(); html=request('index.php')[1]
check('Stored XSS escaped', '&lt;script&gt;alert(1)&lt;/script&gt;' in html and '<script>alert(1)</script>' not in html)
check('Edit XSS escaped','&lt;script&gt;alert(1)&lt;/script&gt;' in request(f'edit.php?id={i}')[1])
payload="' OR '1'='1"
code,body=request('api.php?resource=events&venue='+urllib.parse.quote(payload))
check('SQL injection API',code==200 and json.loads(body)['data']==[])
check('SQL injection HTML','Подій не знайдено' in request('index.php?venue='+urllib.parse.quote(payload))[1])
check('Venue filter', len(json.loads(request('api.php?resource=events&venue=SECURITY_TEST')[1])['data'])>=1)
for path in ['buy.php','delete.php',f'api.php?resource=events&action=buy&id={i}']:
 check('GET blocked '+path,request(path)[0]==405)
for token in [None,'wrong']:
 check('Purchase CSRF rejection',request('buy.php',{'id':i,**({'csrf_token':token} if token else {})})[0]==403)
check('No seats changed',event(i)['seats_left']==2)
for changes in [{'title':''},{'seats_left':'-1'},{'seats_left':'1.5'},{'event_date':'2027-02-30 18:00:00'},{'title[]':'x','title': ''}]:
 check('Invalid input '+str(changes),request('api.php?resource=events',fields|changes,t)[0]==400)
check('No invalid inserts',count()==original+1)
check('Buy PRG',request('buy.php',{'id':i,'csrf_token':t})[0]==303)
check('Seats decrement',event(i)['seats_left']==1)
check('Replay rejected',request('buy.php',{'id':i,'csrf_token':t})[0]==403)
csrf();check('Refresh safe',event(i)['seats_left']==1)
t=csrf();check('API purchase',request(f'api.php?resource=events&action=buy&id={i}',{},t)[0]==200)
t=csrf();check('Sold out rejected',request('buy.php',{'id':i,'csrf_token':t})[0]==409)
check('Seats never negative',event(i)['seats_left']==0)
for action in ['add.php',f'edit.php?id={i}','delete.php']:
 check('CRUD CSRF '+action,request(action,fields|{'id':i})[0]==403)
code,_=request(f'edit.php?id={i}',fields|{'title':'Updated test','csrf_token':t})
check('Edit PRG',code==303);check('Edit persisted',event(i)['title']=='Updated test')
t=csrf();check('Delete PRG',request('delete.php',{'id':i,'csrf_token':t})[0]==303)
check('Fixture deleted',request(f'api.php?resource=events&id={i}')[0]==404)
check('Only test record removed',count()==original)
check('Malformed id',request('api.php?resource=events&id=1abc')[0]==400)
print('ALL CHECKS PASSED')
