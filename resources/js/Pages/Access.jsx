import React,{useEffect,useRef,useState} from 'react';
import {Head,Link,usePage,useForm,router} from '@inertiajs/react';
import {Card,Table,Form,Action,Badge,Stat,number,label,display} from '../Components/UI';
import {Page,field,pick,list} from './Shared';
export function Users({users,roles}){const {auth}=usePage().props;return <Page title="Pengguna & akses"><Card><Table records={users} columns={['name','username','active','roles'].map(key=>({key}))} action={r=><Action url={`/users/${r.id}/toggle`}>Ubah status {r.username}</Action>}/></Card><Card title="Tambah pengguna"><Form action="/users" fields={[field('name'),field('username'),field('email','email'),field('password','password'),pick('role',roles.map(r=>({id:r.name,name:r.name}))),pick('plants',auth.plants,{multiple:true,required:true})]} initial={{plants:[]}}/></Card><Link href="/roles">Lihat role dan permission</Link></Page>}
export function Roles({roles}){return <Page title="Role & permission">{roles.map(r=><Card key={r.id} title={r.name}>{r.permissions.map(p=><Badge key={p.id}>{p.name}</Badge>)}</Card>)}</Page>}
