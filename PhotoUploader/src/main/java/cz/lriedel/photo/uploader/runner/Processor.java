package cz.lriedel.photo.uploader.runner;

import java.util.Map;

public interface Processor {

    void process(Map<String, Object> args) throws Exception;
}
