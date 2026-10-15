import React from 'react';
import {render,screen,fireEvent,cleanup} from '@testing-library/react';
import {afterEach,describe,it,expect} from 'vitest';
import {Form,Table,Badge} from './UI';
afterEach(cleanup);
describe('operational React components',()=>{
 it('keeps nested numeric fields editable with permanent labels',()=>{render(<Form action="/daily-reports" initial={{activities:[{volume:10}]}} fields={[{name:'activities.0.volume',label:'Volume hasil',type:'number',required:true}]}/>);const input=screen.getByLabelText('Volume hasil *');fireEvent.change(input,{target:{value:'25'}});expect(input.value).toBe('25');expect(input.required).toBe(true)});
 it('shows an explicit empty state',()=>{render(<Table records={[]} columns={[{key:'code'}]}/>);expect(screen.getByText('Belum ada data untuk filter ini.')).toBeTruthy()});
 it('escapes untrusted data and keeps status text accessible',()=>{render(<><Badge>APPROVED</Badge><Table records={[{id:1,code:'<script>alert(1)</script>'}]} columns={[{key:'code'}]}/></>);expect(screen.getByText('APPROVED')).toBeTruthy();expect(screen.getByText('<script>alert(1)</script>')).toBeTruthy();expect(document.querySelector('script')).toBeNull()});
});
