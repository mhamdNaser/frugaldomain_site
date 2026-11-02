var e={latin:3.9,arabic:2.4,cjk:1.1,digits:3.2,punctuation:2.6,whitespace:8,other:3},t=RegExp(`\\p{Script=Arabic}`,`u`),n=RegExp(`\\p{Script=Han}|\\p{Script=Hiragana}|\\p{Script=Katakana}|\\p{Script=Hangul}`,`u`),r=RegExp(`\\p{Script=Latin}`,`u`),i=/[0-9]/,a=/\s/,o=/[{}();=<>[\]]|=>|::|\bfunction\b|\bconst\b|\bdef\b|\bimport\b|\bclass\b/;function s(s){let c=String(s??``),l={latin:0,arabic:0,cjk:0,digits:0,punctuation:0,whitespace:0,other:0};for(let e of c)a.test(e)?l.whitespace++:t.test(e)?l.arabic++:n.test(e)?l.cjk++:r.test(e)?l.latin++:i.test(e)?l.digits++:/[\p{P}\p{S}]/u.test(e)?l.punctuation++:l.other++;let u=c.length,d=c.trim()?c.trim().split(/\s+/).length:0,f=c?c.split(`
`).length:0,p=0;for(let[t,n]of Object.entries(l))p+=n/e[t];let m=(c.match(/\b\w{1,3}\b/g)||[]).length;p+=m*.15;let h=o.test(c);h&&(p*=1.08);let g=Object.entries(l).filter(([e])=>![`whitespace`,`punctuation`,`other`].includes(e)).sort((e,t)=>t[1]-e[1])[0];return{characters:u,words:d,lines:f,counts:l,looksLikeCode:h,script:g&&g[1]>0?g[0]:`latin`,tokens:Math.max(+!!c.trim(),Math.round(p)),low:Math.round(p*.82),high:Math.round(p*1.22)}}var c=`2026-09-20`,l=[{id:`anthropic`,name:`Anthropic`,family:`Claude`,counter:`anthropic`,checked:`2026-09-20`,source:`https://platform.claude.com/docs/en/about-claude/pricing`,sourceLabel:`platform.claude.com`},{id:`openai`,name:`OpenAI`,family:`GPT`,counter:`openai`,checked:`2026-09-20`,source:`https://developers.openai.com/api/docs/pricing`,sourceLabel:`developers.openai.com`},{id:`google`,name:`Google`,family:`Gemini`,counter:`google`,checked:`2026-09-20`,source:`https://ai.google.dev/gemini-api/docs/pricing`,sourceLabel:`ai.google.dev`},{id:`xai`,name:`xAI`,family:`Grok`,counter:`compatible`,checked:`2026-09-20`,source:`https://docs.x.ai/docs/models`,sourceLabel:`docs.x.ai`},{id:`deepseek`,name:`DeepSeek`,family:`DeepSeek`,counter:`compatible`,checked:`2026-09-20`,source:`https://api-docs.deepseek.com/quick_start/pricing`,sourceLabel:`api-docs.deepseek.com`},{id:`mistral`,name:`Mistral`,family:`Mistral`,counter:`compatible`,checked:`2026-09-20`,source:`https://mistral.ai/pricing/api`,sourceLabel:`mistral.ai`},{id:`together`,name:`Open weights`,family:`Llama, Qwen, GLM, Kimi`,counter:`compatible`,checked:`2026-09-20`,source:`https://www.together.ai/pricing`,sourceLabel:`together.ai`,note:`An open-weight model is sold by whoever hosts it. These are Together AI's rates; another host will charge something else for the same weights.`}],u=Object.fromEntries(l.map(e=>[e.id,e])),d=[{id:`claude-fable-5-1`,name:`Claude Fable 5.1`,vendor:`anthropic`,context:1e6,input:10,output:50,cachedInput:.25,tokenFactor:1.3},{id:`claude-opus-5`,name:`Claude Opus 5`,vendor:`anthropic`,context:1e6,input:5,output:25,cachedInput:.5,tokenFactor:1.3},{id:`claude-opus-4-8`,name:`Claude Opus 4.8`,vendor:`anthropic`,context:1e6,input:5,output:25,cachedInput:.5,tokenFactor:1.3},{id:`claude-sonnet-5`,name:`Claude Sonnet 5`,vendor:`anthropic`,context:1e6,input:2,output:10,cachedInput:.2,tokenFactor:1.3},{id:`claude-sonnet-4-6`,name:`Claude Sonnet 4.6`,vendor:`anthropic`,context:1e6,input:3,output:15,cachedInput:.3},{id:`claude-haiku-4-5`,name:`Claude Haiku 4.5`,vendor:`anthropic`,context:2e5,input:1,output:5,cachedInput:.1},{id:`gpt-6-astra`,name:`GPT-6 Astra`,vendor:`openai`,context:105e4,input:10,output:50,cachedInput:1},{id:`gpt-5-6-sol`,name:`GPT-5.6 Sol`,vendor:`openai`,context:105e4,input:4,output:20,cachedInput:.4},{id:`gpt-5-6-terra`,name:`GPT-5.6 Terra`,vendor:`openai`,context:105e4,input:2,output:12,cachedInput:.2},{id:`gpt-5-6-luna`,name:`GPT-5.6 Luna`,vendor:`openai`,context:105e4,input:.2,output:1.2,cachedInput:.02},{id:`gpt-5-4`,name:`GPT-5.4`,vendor:`openai`,context:272e3,input:2.5,output:15,cachedInput:.25},{id:`gpt-5-4-mini`,name:`GPT-5.4 mini`,vendor:`openai`,context:null,input:.75,output:4.5,cachedInput:.075},{id:`gpt-5-nano`,name:`GPT-5 nano`,vendor:`openai`,context:null,input:.05,output:.4,cachedInput:.005},{id:`gemini-3-1-pro`,name:`Gemini 3.1 Pro`,vendor:`google`,context:1e6,input:2,output:12,cachedInput:.2,note:`Rises to $4 / $18 on prompts over 200K tokens.`},{id:`gemini-3-8-flash`,name:`Gemini 3.8 Flash`,vendor:`google`,context:1e6,input:.75,output:3.75,cachedInput:.075,note:`Promotional rate to 31 December 2026; doubles on 1 January 2027.`},{id:`gemini-3-5-flash`,name:`Gemini 3.5 Flash`,vendor:`google`,context:1e6,input:1.5,output:9,cachedInput:.15},{id:`gemini-3-5-flash-lite`,name:`Gemini 3.5 Flash-Lite`,vendor:`google`,context:null,input:.3,output:2.5,cachedInput:.03},{id:`gemini-2-5-flash-lite`,name:`Gemini 2.5 Flash-Lite`,vendor:`google`,context:null,input:.1,output:.4,cachedInput:.01,note:`Text, image and video input; audio input is priced higher.`},{id:`grok-4-6`,name:`Grok 4.6`,vendor:`xai`,context:5e5,input:2,output:6,cachedInput:.5,note:`Doubles for a request whose prompt reaches 200K tokens.`},{id:`grok-4-3`,name:`Grok 4.3`,vendor:`xai`,context:1e6,input:1.25,output:2.5,cachedInput:.2,note:`Doubles for a request whose prompt reaches 200K tokens.`},{id:`deepseek-v4-pro`,name:`DeepSeek V4 Pro`,vendor:`deepseek`,context:1e6,input:1.32,output:3.96,cachedInput:.044,note:`Peak rate. Half price outside 01:00-04:00 and 06:00-10:00 UTC on weekdays.`},{id:`deepseek-flash`,name:`DeepSeek Flash`,vendor:`deepseek`,context:1e6,input:.3,output:1.2,cachedInput:.006,note:`Peak rate. Half price outside 01:00-04:00 and 06:00-10:00 UTC on weekdays.`},{id:`mistral-medium-3-5`,name:`Mistral Medium 3.5`,vendor:`mistral`,context:null,input:1.5,output:7.5,cachedInput:null},{id:`mistral-small-4`,name:`Mistral Small 4`,vendor:`mistral`,context:null,input:.15,output:.6,cachedInput:null},{id:`codestral`,name:`Codestral`,vendor:`mistral`,context:null,input:.3,output:.9,cachedInput:null},{id:`llama-3-3-70b`,name:`Llama 3.3 70B`,vendor:`together`,context:null,input:1.04,output:1.04,cachedInput:null},{id:`qwen-3-8`,name:`Qwen3.8 (2.4T-A95B)`,vendor:`together`,context:null,input:2,output:6,cachedInput:null},{id:`qwen-3-8-flash`,name:`Qwen3.8 Flash`,vendor:`together`,context:null,input:.15,output:.47,cachedInput:null},{id:`glm-5-3`,name:`GLM-5.3`,vendor:`together`,context:null,input:1.4,output:4.4,cachedInput:null},{id:`kimi-k3`,name:`Kimi K3`,vendor:`together`,context:null,input:3,output:15,cachedInput:null}],f={anthropic:{label:`Anthropic`,lede:`A free endpoint that runs the model's own tokeniser and charges nothing for the call.`,snippets:{curl:e=>`curl https://api.anthropic.com/v1/messages/count_tokens \\
  -H "x-api-key: $ANTHROPIC_API_KEY" \\
  -H "anthropic-version: 2023-06-01" \\
  -H "content-type: application/json" \\
  -d '{
    "model": "${e}",
    "messages": [{ "role": "user", "content": "your text here" }]
  }'`,typescript:e=>`import Anthropic from "@anthropic-ai/sdk";

const client = new Anthropic();

const { input_tokens } = await client.messages.countTokens({
  model: "${e}",
  messages: [{ role: "user", content: text }],
});

console.log(input_tokens);`,python:e=>`import anthropic

client = anthropic.Anthropic()

count = client.messages.count_tokens(
    model="${e}",
    messages=[{"role": "user", "content": text}],
)

print(count.input_tokens)`}},openai:{label:`OpenAI`,lede:`Counted offline with tiktoken. A model the installed version has never heard of raises KeyError - upgrade the package rather than guessing at an encoding name.`,snippets:{python:e=>`import tiktoken

encoding = tiktoken.encoding_for_model("${e}")

print(len(encoding.encode(text)))`,typescript:e=>`import { encoding_for_model } from "tiktoken";

const encoding = encoding_for_model("${e}");
const tokens = encoding.encode(text);

console.log(tokens.length);
encoding.free();`,curl:()=>`# tiktoken is a library, not an endpoint. To read the count the
# API itself billed, send the request and look at the usage block
# that comes back with the response:

curl https://api.openai.com/v1/responses \\
  -H "Authorization: Bearer $OPENAI_API_KEY" \\
  -H "content-type: application/json" \\
  -d '{ "model": "gpt-5.6-terra", "input": "your text here" }' \\
  | jq .usage`}},google:{label:`Google`,lede:`The SDK asks the service to count, with the tokeniser that model uses. The call is not billed.`,snippets:{python:e=>`from google import genai

client = genai.Client()

result = client.models.count_tokens(
    model="${e}",
    contents=text,
)

print(result.total_tokens)`,typescript:e=>`import { GoogleGenAI } from "@google/genai";

const ai = new GoogleGenAI({});

const { totalTokens } = await ai.models.countTokens({
  model: "${e}",
  contents: text,
});

console.log(totalTokens);`,curl:e=>`curl "https://generativelanguage.googleapis.com/v1beta/models/${e}:countTokens" \\
  -H "x-goog-api-key: $GEMINI_API_KEY" \\
  -H "content-type: application/json" \\
  -d '{ "contents": [{ "parts": [{ "text": "your text here" }] }] }'`}},compatible:{label:`OpenAI-compatible APIs`,lede:`xAI, DeepSeek, Mistral, Together and most other hosts speak the OpenAI protocol and report what they counted in usage. Asking for a single output token makes that the cheapest exact count you can get.`,snippets:{python:e=>`from openai import OpenAI

# point base_url at the provider you are pricing
client = OpenAI(base_url="https://api.provider.com/v1", api_key=KEY)

response = client.chat.completions.create(
    model="${e}",
    messages=[{"role": "user", "content": text}],
    max_tokens=1,
)

print(response.usage.prompt_tokens)`,typescript:e=>`import OpenAI from "openai";

// point baseURL at the provider you are pricing
const client = new OpenAI({ baseURL: "https://api.provider.com/v1", apiKey: KEY });

const response = await client.chat.completions.create({
  model: "${e}",
  messages: [{ role: "user", content: text }],
  max_tokens: 1,
});

console.log(response.usage.prompt_tokens);`,curl:e=>`curl https://api.provider.com/v1/chat/completions \\
  -H "Authorization: Bearer $API_KEY" \\
  -H "content-type: application/json" \\
  -d '{
    "model": "${e}",
    "messages": [{ "role": "user", "content": "your text here" }],
    "max_tokens": 1
  }' | jq .usage`}}},p={anthropic:`claude-opus-5`,openai:`gpt-5.6-terra`,google:`gemini-3.8-flash`,compatible:`deepseek-v4-pro`};function m({inputTokens:e,outputTokens:t,model:n,cachedShare:r=0}){let i=n.tokenFactor||1,a=e*i,o=t*i,s=typeof n.cachedInput==`number`?n.cachedInput:n.input,c=Math.min(1,Math.max(0,r))*a,l=(a-c)/1e6*n.input,u=c/1e6*s,d=o/1e6*n.output;return{input:l+u,output:d,total:l+u+d,inputTokens:Math.round(a),outputTokens:Math.round(o),cacheKnown:typeof n.cachedInput==`number`}}function h(e){return Number.isFinite(e)?e===0?`$0`:e<.01?`$${e.toFixed(5)}`:e<1?`$${e.toFixed(4)}`:e<1e3?`$${e.toFixed(2)}`:`$${Math.round(e).toLocaleString(`en-US`)}`:`-`}function g(e){return Number(e||0).toLocaleString(`en-US`)}function _(e){if(!e)return null;if(e>=1e6){let t=e/1e6;return`${t%1==0?t:t.toFixed(2)}M context`}return`${Math.round(e/1e3)}K context`}export{h as a,c,u as d,_ as i,f as l,d as n,p as o,m as r,s,g as t,l as u};