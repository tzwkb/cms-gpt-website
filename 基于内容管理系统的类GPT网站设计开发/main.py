from typing import Union, List, Dict
from fastapi import FastAPI, HTTPException, Depends
from pydantic import BaseModel
from zhipuai import ZhipuAI
import logging
import time
from uuid import uuid4

# 初始化FastAPI应用
app = FastAPI()

# 初始化日志记录
logging.basicConfig(level=logging.INFO)

# 初始化质普清言API客户端
client = ZhipuAI(api_key="9bb1a129d84f1f5db97393b35b909123.Vx6nhnD0E0SsUpBk")  # 填写您自己的APIKey

# 用于存储每个会话的上下文信息
sessions = {}

class TMXLineRequest(BaseModel):
    source_lang: str
    target_lang: str
    source_text: str
    target_text: str

class TXTLineRequest(BaseModel):
    language: str
    text: str

class SentenceEmbeddingRequest(BaseModel):
    text: str

class ChatRequest(BaseModel):
    session_id: str
    text: str
    context: str = None

@app.post("/chat/")
async def chat(request: ChatRequest):
    logging.info(f"Received request: {request.text}, context:{request.context}")

    try:
        session_id = request.session_id
        #session_id = ""
        user_message = request.text

        # 构造用户的问题文本，包括上下文信息
        message = f"请基于下面的文本:\n{request.context}\n回答以下问题:\n{request.text}" if request.context else f"回答以下问题:\n{request.text}"

        # 初始化或获取会话历史消息
        messages = sessions.setdefault(session_id, [])
        #messages = sessions.setdefault('',[])
        

        # 将用户消息添加到会话中
        messages.append({"role": "user", "content": user_message})

        # 请求助理生成响应
        response = client.chat.completions.create(
            model="glm-4",
            messages=[{"role": "user", "content": message}],
        )

        # 获取助理生成的响应文本
        assistant_message = response.choices[0].message.content

        # 将助理的响应添加到会话中
        messages.append({"role": "assistant", "content": assistant_message})

        # 记录日志
        logging.info(f"Chat session {session_id} - User: {user_message}, Assistant: {assistant_message}")

        # 返回响应给客户端
        return {"session_id": session_id, "assistant_message": assistant_message}
        #return {"assistant_message": assistant_message}
    
    except KeyError as e:
        raise HTTPException(status_code=404, detail=f"Session ID {session_id} not found: {str(e)}")
    
    except Exception as e:
        logging.error(f"Error occurred during chat: {str(e)}")
        raise HTTPException(status_code=500, detail=str(e))

@app.post("/process-tmx-line/")
async def process_tmx_line(request: TMXLineRequest):
    try:
        logging.info(f"Received TMX line request: {request}")

        source_lang = request.source_lang
        target_lang = request.target_lang
        source_text = request.source_text
        target_text = request.target_text

        # 发送源语言文本内容到 zhipuai API 进行向量化
        source_response = client.embeddings.create(
            model="embedding-2",
            input=source_text,
        )
        source_embedding = source_response.data[0].embedding

        # 发送目标语言文本内容到 zhipuai API 进行向量化
        target_response = client.embeddings.create(
            model="embedding-2",
            input=target_text,
        )
        target_embedding = target_response.data[0].embedding

        logging.info(f"Source embedding: {source_embedding}")
        logging.info(f"Target embedding: {target_embedding}")

        # 返回源语言和目标语言的向量
        return {"source_embedding": source_embedding, "target_embedding": target_embedding}
    except Exception as e:
        logging.error(f"Error occurred while processing TMX line: {str(e)}")
        raise HTTPException(status_code=500, detail=str(e))
    
@app.post("/process-text/")
async def process_txt_line(request: TXTLineRequest):
    try:
        logging.info(f"Received TXT line request: {request}")

        language = request.language
        text = request.text

        # 发送文本内容到 zhipuai API 进行向量化
        response = client.embeddings.create(
            model="embedding-2",
            input=text,
        )
        embedding = response.data[0].embedding

        logging.info(f"Embedding: {embedding}")

        # 返回文本的向量
        return {"embedding": embedding}
    except Exception as e:
        logging.error(f"Error occurred while processing TXT line: {str(e)}")
        raise HTTPException(status_code=500, detail=str(e))

@app.post("/get-sentence-embedding/")
async def get_sentence_embedding(request: SentenceEmbeddingRequest):
    try:
        logging.info(f"Received sentence embedding request: {request}")

        text = request.text

        response = client.embeddings.create(
            model="embedding-2",
            input=text,
        )
        embedding = response.data[0].embedding

        #logging.info(f"Sentence embedding: {embedding}")

        return {"embedding": embedding}
    except Exception as e:
        logging.error(f"Error occurred while getting sentence embedding: {str(e)}")
        raise HTTPException(status_code=500, detail=str(e))

